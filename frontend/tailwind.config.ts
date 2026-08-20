import type { Config } from 'tailwindcss';

const config: Config = {
  content: ['./app/**/*.{ts,tsx}', './components/**/*.{ts,tsx}', './features/**/*.{ts,tsx}', './lib/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        brand: 'var(--brand-gold)',
        charcoal: 'var(--brand-charcoal)',
        ink: 'var(--text-primary)',
        muted: 'var(--text-secondary)',
        surface: 'var(--surface)',
        canvas: 'var(--background)',
        line: 'var(--border)',
      },
      borderRadius: {
        sm: 'var(--radius-sm)',
        md: 'var(--radius-md)',
        lg: 'var(--radius-lg)',
      },
      boxShadow: {
        soft: 'var(--shadow-soft)',
        lift: 'var(--shadow-lift)',
      },
      fontFamily: {
        sans: ['var(--font-ui)', 'sans-serif'],
      },
    },
  },
  plugins: [],
};

export default config;
