import Vuex from 'vuex';

import messages from './messages';
import departures from './departures';

export default new Vuex.Store({
    modules: { messages, departures }
})