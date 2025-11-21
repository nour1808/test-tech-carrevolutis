import Head from 'next/head';
import { useRouter } from 'next/router';
import { useMemo, useState } from 'react';
import Toast from '../../components/Toast';

const apiBase = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:8080';

export default function OfferPage() {
  const router = useRouter();
  const { id } = router.query;
  const [email, setEmail] = useState('');
  const [cvUrl, setCvUrl] = useState('');
  const [loading, setLoading] = useState(false);
  const [toast, setToast] = useState({ message: '', type: 'success' });

  const offerId = useMemo(() => {
    if (!id) return null;
    const parsed = parseInt(id, 10);
    return Number.isNaN(parsed) ? null : parsed;
  }, [id]);

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!offerId) {
      setToast({ message: 'Identifiant d’offre invalide', type: 'error' });
      return;
    }

    setLoading(true);
    try {
      const res = await fetch(`${apiBase}/apply`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          offer_id: offerId,
          email,
          cv_url: cvUrl,
        }),
      });

      const payload = await res.json().catch(() => ({}));

      if (res.ok) {
        setToast({ message: '🎉 Candidature envoyée !', type: 'success' });
        setEmail('');
        setCvUrl('');
      } else {
        const errors = payload?.errors;
        const message =
          errors && typeof errors === 'object'
            ? Object.values(errors).join(' / ')
            : payload?.error || '💥 Oups, erreur !';

        setToast({ message, type: 'error' });
      }
    } catch (e) {
      setToast({ message: '💥 Oups, erreur !', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <Head>
        <title>Candidater à l&apos;offre {offerId ?? ''}</title>
        <link
          href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap"
          rel="stylesheet"
        />
      </Head>
      <main
        style={{
          minHeight: '100vh',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          padding: 24,
          background: 'radial-gradient(circle at 15% 30%, #f6fff8 0, #f0f4f8 25%, #e6ebf2 55%, #dfe6ee 100%)',
          fontFamily: '"Space Grotesk", "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        }}
      >
        <section
          style={{
            width: '100%',
            maxWidth: 460,
            background: '#fff',
            borderRadius: 16,
            padding: '28px 28px 24px 28px',
            boxShadow: '0 18px 50px rgba(0, 0, 0, 0.12)',
            border: '1px solid #e6e8ec',
          }}
        >
          <p style={{ textTransform: 'uppercase', letterSpacing: 1, color: '#5a6a85', fontWeight: 700, margin: 0 }}>
            Offre #{offerId ?? '...'}
          </p>
          <h1 style={{ margin: '4px 0 14px 0', fontSize: 24, color: '#0f172a' }}>Candidater à cette offre</h1>
          <p style={{ margin: '0 0 18px 0', color: '#4a5568', lineHeight: 1.5 }}>
            Déposez votre email et le lien vers votre CV. Nous vous confirmons la réception instantanément.
          </p>

          <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
            <label style={{ display: 'flex', flexDirection: 'column', gap: 6, fontWeight: 600, color: '#111827' }}>
              Email
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="votre.email@mail.com"
                style={inputStyle}
              />
            </label>

            <label style={{ display: 'flex', flexDirection: 'column', gap: 6, fontWeight: 600, color: '#111827' }}>
              Lien vers votre CV
              <input
                type="url"
                required
                value={cvUrl}
                onChange={(e) => setCvUrl(e.target.value)}
                placeholder="https://mon-cv.pdf"
                style={inputStyle}
              />
            </label>

            <button
              type="submit"
              disabled={loading}
              style={{
                marginTop: 6,
                background: loading ? '#a5b4fc' : '#4338ca',
                color: '#fff',
                border: 'none',
                borderRadius: 12,
                padding: '12px 16px',
                fontWeight: 700,
                cursor: loading ? 'not-allowed' : 'pointer',
                boxShadow: '0 12px 24px rgba(67, 56, 202, 0.25)',
                transition: 'transform 120ms ease, box-shadow 120ms ease',
              }}
            >
              {loading ? 'Envoi…' : 'Envoyer ma candidature'}
            </button>
          </form>
        </section>

        <Toast
          message={toast.message}
          type={toast.type}
          onClose={() => setToast({ message: '', type: 'success' })}
        />
      </main>
    </>
  );
}

const inputStyle = {
  padding: '12px 14px',
  borderRadius: 10,
  border: '1px solid #d3dae6',
  fontSize: 15,
  color: '#0f172a',
  outline: 'none',
  background: '#f8fafc',
};
