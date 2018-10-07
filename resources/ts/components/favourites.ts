import Vue from 'vue'
import { Component } from 'vue-property-decorator'
import { namespace } from "vuex-class";
import { Favourite } from "../store/favourites";
import { SavedState } from "../store/root";

const { State, Mutation } = namespace('favourites');

@Component({ template: require('../../components/favourites.html' )})
export class FavouritesComponent extends Vue {
    @State favourites: Favourite[];
    @Mutation remove: (fav: Favourite) => void;

    choose(favourite: Favourite) {
        this.$store.dispatch('load', favourite.state);
    }
}

@Component({ template: require('../../components/favourites/save.html' )})
export class FavouritesAdderComponent extends Vue {
    private name = "";

    @Mutation add: (fav: Favourite) => void;

    async save() {
        const state = await this.$store.dispatch('save') as SavedState;
        const name  = this.name;

        const favourite: Favourite = { name, state };

        this.add(favourite);
        this.name = '';

        this.$emit('saved', favourite);
    }
}

Vue.component('Favourites', FavouritesComponent);
Vue.component('FavouritesAdder', FavouritesAdderComponent);
