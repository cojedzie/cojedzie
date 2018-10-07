import Vuex from 'vuex';

import messages from './messages';
import departures from './departures'
import favourites, { localStorageSaver } from './favourites'

import { state, mutations, actions } from "./root";

export default new Vuex.Store({
    state, mutations, actions,
    modules: { messages, departures, favourites },
    plugins: [
        localStorageSaver('favourites.favourites', 'favourites'),
    ]
})