import '../css/app.css';
import './bootstrap';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    title: title => title ? `${title} • TKA Juara` : 'TKA Juara',
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.jsx');
        return pages[`./Pages/${name}.jsx`]();
    },
    setup({ el, App, props }) { createRoot(el).render(<App {...props} />); },
    progress: { color: '#0b63ce' },
});
