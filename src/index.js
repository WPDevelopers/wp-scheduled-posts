const { render } = wp.element;
import App from './App';
import { AppProvider } from './context/AppContext';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('wpsp-post-panel-react-root');
    if (root) {
        render( <AppProvider> <App />  </AppProvider>, root);
    }
});