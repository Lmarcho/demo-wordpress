const colors = require('tailwindcss/colors');

module.exports = {
	//prefix: "sc-",
	content: [
		'./app/src/**/*.{js,jsx}',
		'./control-block/src/**/*.{js,jsx}'
	],
	safelist: ['bg-alert-success-bg', 'text-alert-success-text', 'bg-alert-error-bg', 'text-alert-error-text', 'bg-alert-warning-bg', 'text-alert-warning-text'],
	important: true,
	theme: {
		extend: {
			boxShadow: {
				'template-inputs-btns': '-1px 0px 2px rgba(0, 0, 0, 0.06), -1px 0px 3px rgba(0, 0, 0, 0.1)',
			},
			colors: {
				sky: colors.sky,
				teal: colors.teal,
				'blue-gray': colors.slate,
				'app-primary': '#145485',
				'app-primary-hover': '#3d71ff',
				'app-secondary': '#EFF6FF',
				'app-secondary-hover': '#bfdbfe',
				'app-heading': '#334155',
				'app-text': '#64748b',
				'theme-body': '#4B5563',
				'app-heading-color': '#1F2937',
				'app-background': '#F4F7FB',
				'app-container': '#ffffff',
				'app-border': '#e2e8f0',
				'app-inactive-icon': '#94a3b8',
				'app-active-icon': '#475569',
				'app-table-row': '#f8fafc',
				'alert-info': '#3b82f6',
				'alert-info-bg': '#eff6ff',
				'alert-info-text': '#2563eb',
				'alert-success': '#22c55e',
				'alert-success-bg': '#f0fdf4',
				'alert-success-text': '#16a34a',
				'alert-error': '#ef4444',
				'alert-error-bg': '#fef2f2',
				'alert-error-text': '#dc2626',
				'alert-warning-text': '#CA8A04',
				'alert-warning-bg': '#FEFCE8',
				'trash-border': '#FDE7F2',
				'secondary-sky': '#CDE3FE',
			},
			spacing: {
				'18': '4.5rem',
				'160': '40rem',
				'full-logo-width': '154px',
				'clock-icon-bg': '72px',
			}
		},
		fontFamily: {
			// Use inherited font family to avoid global overrides
			'sans': ['inherit'],
			// Custom font family for scoped usage
			'suretriggers': ['Figtree', 'Inter', 'sans-serif'],
		}
	},
	plugins: [
		require('@tailwindcss/forms'),
	],
};
