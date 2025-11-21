<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;

class ApplicationRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function recordMetric(string $name): void
    {
        $this->connection->transactional(function (Connection $conn) use ($name): void {
            $updated = $conn->createQueryBuilder()
                ->update('metrics')
                ->set('cnt', 'cnt + 1')
                ->where('name = :name')
                ->setParameter('name', $name, ParameterType::STRING)
                ->executeStatement();

            if ($updated === 0) {
                try {
                    $conn->createQueryBuilder()
                        ->insert('metrics')
                        ->values([
                            'name' => ':name',
                            'cnt' => ':cnt',
                        ])
                        ->setParameter('name', $name, ParameterType::STRING)
                        ->setParameter('cnt', 1, ParameterType::INTEGER)
                        ->executeStatement();
                } catch (UniqueConstraintViolationException $e) {
                    $conn->createQueryBuilder()
                        ->update('metrics')
                        ->set('cnt', 'cnt + 1')
                        ->where('name = :name')
                        ->setParameter('name', $name, ParameterType::STRING)
                        ->executeStatement();
                }
            }
        });
    }

    /**
     * @return array{created: bool, id: int}
     */
    public function createOrGetApplication(int $offerId, string $email, string $cvUrl): array
    {
        return $this->connection->transactional(function (Connection $conn) use ($offerId, $email, $cvUrl): array {
            $insert = $conn->createQueryBuilder();
            $insert
                ->insert('applications')
                ->values([
                    'offer_id' => ':offer_id',
                    'email' => ':email',
                    'cv_url' => ':cv_url',
                ])
                ->setParameter('offer_id', $offerId, ParameterType::INTEGER)
                ->setParameter('email', $email, ParameterType::STRING)
                ->setParameter('cv_url', $cvUrl, ParameterType::STRING);

            try {
                $insert->executeStatement();
                $applicationId = (int) $conn->lastInsertId();

                return ['created' => true, 'id' => $applicationId];
            } catch (UniqueConstraintViolationException $e) {
                $existingId = $conn->createQueryBuilder()
                    ->select('id')
                    ->from('applications')
                    ->where('offer_id = :offer_id')
                    ->andWhere('email = :email')
                    ->setParameter('offer_id', $offerId, ParameterType::INTEGER)
                    ->setParameter('email', $email, ParameterType::STRING)
                    ->setMaxResults(1)
                    ->executeQuery()
                    ->fetchOne();

                if ($existingId === false) {
                    throw $e;
                }

                return ['created' => false, 'id' => (int) $existingId];
            }
        });
    }

    public function findApplicationById(int $id): ?array
    {
        $application = $this->connection->createQueryBuilder()
            ->select('id', 'offer_id', 'email', 'cv_url', 'created_at')
            ->from('applications')
            ->where('id = :id')
            ->setParameter('id', $id, ParameterType::INTEGER)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $application ?: null;
    }

    public function fetchStats(): array
    {
        $applicationsTotal = (int) $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('applications')
            ->executeQuery()
            ->fetchOne();

        $metricStmt = $this->connection->createQueryBuilder()
            ->select('name', 'cnt')
            ->from('metrics')
            ->executeQuery()
            ->fetchAllAssociative();

        $metrics = ['success' => 0, 'failed' => 0];

        foreach ($metricStmt as $row) {
            $metrics[$row['name']] = (int) $row['cnt'];
        }

        return [
            'applications_total' => $applicationsTotal,
            'metrics' => $metrics,
        ];
    }
}
