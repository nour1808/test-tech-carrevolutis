import Head from 'next/head';
import { useEffect, useState, useMemo } from 'react';

const apiBase = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8080';

const resolveApi = () => {
  if (typeof window === 'undefined') return apiBase;
  // If the env points to an internal Docker hostname (ex: http://api), fall back to same-origin host.
  try {
    const url = new URL(apiBase);
    if (['api', 'localhost'].includes(url.hostname) || url.hostname === window.location.hostname) {
      return `${window.location.protocol}//${window.location.host.replace(/:\d+$/, ':8080')}`;
    }
  } catch (e) {
    return apiBase;
  }
  return apiBase;
};

export default function ApplicationsPage() {
  const [applications, setApplications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    const load = async () => {
      setLoading(true);
      setError('');
      try {
        const res = await fetch(`${resolveApi()}/applications`);
        const payload = await res.json().catch(() => ({}));
        if (!res.ok) {
          throw new Error(payload?.error || 'Impossible de charger les candidatures');
        }
        if (active) setApplications(payload.applications || []);
      } catch (e) {
        if (active) setError(e.message || 'Erreur inattendue');
      } finally {
        if (active) setLoading(false);
      }
    };
    load();
    return () => {
      active = false;
    };
  }, []);

  const stats = useMemo(() => {
    return {
      total: applications.length,
      offers: new Set(applications.map((a) => a.offer_id)).size,
    };
  }, [applications]);

  return (
    <>
      <Head>
        <title>Applications | Carrevolutis</title>
        <link
          href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap"
          rel="stylesheet"
        />
      </Head>

      <main style={pageStyle}>
        <header style={heroStyle}>
          <div>
            <p style={eyebrowStyle}>Tableau des candidatures</p>
            <h1 style={titleStyle}>Vision claire sur toutes les applies</h1>
            <p style={subtitleStyle}>
              Suivez en temps réel les candidatures reçues, par offre et par email, avec un rendu fluide et lisible.
            </p>
            <div style={pillRowStyle}>
              <div style={pillStyle}>
                <span style={pillLabelStyle}>Candidatures</span>
                <strong style={pillValueStyle}>{stats.total}</strong>
              </div>
              <div style={pillStyle}>
                <span style={pillLabelStyle}>Offres concernées</span>
                <strong style={pillValueStyle}>{stats.offers}</strong>
              </div>
            </div>
          </div>
          <div style={badgeStyle}>Live</div>
        </header>

        <section style={cardStyle}>
          {loading && <div style={placeholderStyle}>Chargement des candidatures…</div>}
          {error && <div style={errorStyle}>{error}</div>}

          {!loading && !error && (
            <div style={listStyle}>
              {applications.length === 0 ? (
                <div style={placeholderStyle}>Aucune candidature pour le moment.</div>
              ) : (
                applications.map((app) => (
                  <article key={app.id} style={itemStyle}>
                    <div style={{ display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                      <div style={avatarStyle}>{app.email.slice(0, 2).toUpperCase()}</div>
                      <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                        <p style={itemEmailStyle}>{app.email}</p>
                        <p style={itemMetaStyle}>
                          Offre #{app.offer_id} · #{app.id}
                        </p>
                      </div>
                    </div>
                    <div style={itemRightStyle}>
                      <a href={app.cv_url} target="_blank" rel="noreferrer" style={linkStyle}>
                        CV
                      </a>
                      <span style={timeStyle}>{new Date(app.created_at).toLocaleString()}</span>
                    </div>
                  </article>
                ))
              )}
            </div>
          )}
        </section>
      </main>
    </>
  );
}

const pageStyle = {
  minHeight: '100vh',
  padding: '32px 20px 48px',
  background: 'linear-gradient(150deg, #f6f9ff 0%, #eef4ff 48%, #e9f2ff 100%)',
  fontFamily: '"Space Grotesk", "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
};

const heroStyle = {
  maxWidth: 1080,
  margin: '0 auto 22px',
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center',
  gap: 20,
};

const eyebrowStyle = {
  margin: 0,
  letterSpacing: 1,
  textTransform: 'uppercase',
  color: '#5b6b85',
  fontWeight: 700,
  fontSize: 13,
};

const titleStyle = { margin: '6px 0 10px', fontSize: 32, color: '#0f172a' };
const subtitleStyle = { margin: 0, maxWidth: 720, color: '#42526c', lineHeight: 1.5, fontSize: 15 };

const pillRowStyle = { display: 'flex', gap: 10, marginTop: 14, flexWrap: 'wrap' };
const pillStyle = {
  background: '#fff',
  borderRadius: 12,
  padding: '10px 14px',
  border: '1px solid #e5e7eb',
  boxShadow: '0 12px 30px rgba(15,23,42,0.08)',
  display: 'flex',
  flexDirection: 'column',
  gap: 4,
  minWidth: 140,
};
const pillLabelStyle = { color: '#6b7280', fontSize: 12, letterSpacing: 0.3 };
const pillValueStyle = { color: '#111827', fontSize: 18 };

const badgeStyle = {
  background: '#22c55e',
  color: '#0b1f0f',
  fontWeight: 700,
  padding: '10px 14px',
  borderRadius: 14,
  boxShadow: '0 14px 30px rgba(34,197,94,0.35)',
  letterSpacing: 0.4,
};

const cardStyle = {
  maxWidth: 1080,
  margin: '0 auto',
  background: '#fff',
  borderRadius: 16,
  border: '1px solid #e5e7eb',
  boxShadow: '0 16px 40px rgba(0,0,0,0.08)',
  padding: 18,
};

const listStyle = { display: 'flex', flexDirection: 'column', gap: 12 };

const itemStyle = {
  display: 'flex',
  justifyContent: 'space-between',
  alignItems: 'center',
  padding: '14px 12px',
  borderRadius: 12,
  border: '1px solid #eef1f6',
  background: '#f8fafc',
  boxShadow: '0 6px 16px rgba(15,23,42,0.06)',
  gap: 12,
  flexWrap: 'wrap',
};

const avatarStyle = {
  width: 42,
  height: 42,
  borderRadius: '12px',
  background: 'linear-gradient(135deg, #4f46e5, #7c3aed)',
  color: '#fff',
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  fontWeight: 800,
  letterSpacing: 1,
};

const itemEmailStyle = { margin: 0, fontWeight: 700, color: '#0f172a', fontSize: 16 };
const itemMetaStyle = { margin: 0, color: '#6b7280', fontSize: 13 };

const itemRightStyle = { display: 'flex', alignItems: 'center', gap: 10, flexWrap: 'wrap' };

const linkStyle = {
  padding: '8px 12px',
  background: '#0ea5e9',
  color: '#fff',
  borderRadius: 10,
  textDecoration: 'none',
  fontWeight: 700,
  boxShadow: '0 10px 20px rgba(14,165,233,0.25)',
};

const timeStyle = { color: '#475569', fontSize: 12, fontVariantNumeric: 'tabular-nums' };
const placeholderStyle = { padding: '16px 12px', textAlign: 'center', color: '#475569' };
const errorStyle = { ...placeholderStyle, color: '#b91c1c' };
