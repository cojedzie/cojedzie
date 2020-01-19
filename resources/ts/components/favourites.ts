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
    private errors = { name: [] };

    @Mutation add: (fav: Favourite) => void;

    async save() {
        const state = await this.$store.dispatch('save') as SavedState;
        const name  = this.name;

        const favourite: Favourite = { name, state };

        if (this.validate(favourite)) {
            this.add(favourite);
            this.name = '';

            this.$emit('saved', favourite);
        }
    }

    private validate(favourite: Favourite) {
        let errors = { name: [] };

        if (favourite.name.length == 0) {
            errors.name.push("Musisz podać nazwę.");
        }

        if (this.$store.state.favourites.favourites.filter(other => other.name == favourite.name).length > 0) {
            errors.name.push("Istnieje już zapisana grupa przystanków o takiej nazwie.");
        }

        this.errors = errors;

        return Object.entries(errors).map(a => a[1]).reduce((acc, cur) => [ ...acc, ...cur ]).length == 0;
    }
}

Vue.component('Favourites', FavouritesComponent);
Vue.component('FavouritesAdder', FavouritesAdderComponent);
