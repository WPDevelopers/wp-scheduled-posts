const { render } = wp.element;
import App from './App';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('wpsp-post-panel-react-root');
    if (root) {
        render(<App />, root);
    }
});