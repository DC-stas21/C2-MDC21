import axios from 'axios';

interface AxiosWindow extends Window {
    axios: typeof axios;
}

declare let window: AxiosWindow;

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
