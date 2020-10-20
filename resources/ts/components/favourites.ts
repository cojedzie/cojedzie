import Vue from 'vue'
import { Component, Watch } from 'vue-property-decorator'
import { Mutation, State } from "vuex-class";
import { Favourite } from "../store/favourites";
import { Stop } from "../model";
import * as uuid from "uuid";
import { Favourites } from "../store";


@Component({ template: require('../../components/favourites.html' )})
export class FavouritesComponent extends Vue {
    @Favourites.State favourites: Favourite[];
    @Favourites.Mutation remove: (fav: Favourite) => void;
    @Mutation('replace') setStops: (stops: Stop[]) => void;

    choose(favourite: Favourite) {
        this.setStops(favourite.stops);
    }
}

function createFavouriteEntry(name: string, stops: Stop[]): Favourite {
    return {
        id: uuid.v4(),
        name,
        stops
    }
}

@Component({ template: require('../../components/favourites/save.html' )})
export class FavouritesAdderComponent extends Vue {
    @State stops: Stop[];

    private name = "";
    private errors = { name: [] };

    private confirmation = false;

    @Favourites.Mutation add: (favourite: Favourite) => void;

    @Watch('name')
    handleNameChange() {
        this.confirmation = false;
    }

    async save() {
        const favourite: Favourite = createFavouriteEntry(this.name, this.stops);

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

        if (this.$store.state.favourites.favourites.filter(other => other.name == favourite.name).length > 0 && !this.confirmation) {
            errors.name.push("Istnieje już zapisana grupa przystanków o takiej nazwie.");
            this.confirmation = true;
        }

        this.errors = errors;

        return Object.entries(errors).map(a => a[1]).reduce((acc, cur) => [ ...acc, ...cur ]).length == 0;
    }
}

Vue.component('Favourites', FavouritesComponent);
Vue.component('FavouritesAdder', FavouritesAdderComponent);
