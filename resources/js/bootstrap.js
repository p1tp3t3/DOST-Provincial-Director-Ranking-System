import axios from 'axios';
//import 'bootstrap/dist/css/bootstrap.css';
//import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
