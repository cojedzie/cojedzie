import Vuex from 'vuex';

import messages from './messages';
import departures from './departures';
import { state, mutations, actions } from "./root";

export default new Vuex.Store({
    state, mutations, actions,
    modules: { messages, departures }
})