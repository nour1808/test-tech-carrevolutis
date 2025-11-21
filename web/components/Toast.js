import { useEffect } from 'react';

export default function Toast({ message, type = 'info', onClose }) {
  useEffect(() => {
    if (!message) return;
    const timer = setTimeout(() => onClose?.(), 3200);
    return () => clearTimeout(timer);
  }, [message, onClose]);

  if (!message) return null;

  const isSuccess = type === 'success';
  const background = isSuccess ? '#e6f7ed' : '#fde8e8';
  const border = isSuccess ? '#2f855a' : '#c53030';
  const color = isSuccess ? '#1c4532' : '#742a2a';

  return (
    <div
      role="status"
      style={{
        position: 'fixed',
        top: 16,
        right: 16,
        background,
        color,
        border: `1px solid ${border}`,
        borderRadius: 8,
        padding: '12px 14px',
        boxShadow: '0 12px 30px rgba(0,0,0,0.12)',
        zIndex: 1000,
        minWidth: 240,
        fontWeight: 600,
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 8 }}>
        <span>{message}</span>
        <button
          aria-label="Fermer le toast"
          onClick={onClose}
          style={{
            border: 'none',
            background: 'transparent',
            color,
            cursor: 'pointer',
            fontSize: 16,
            padding: 2,
          }}
        >
          ×
        </button>
      </div>
    </div>
  );
}
