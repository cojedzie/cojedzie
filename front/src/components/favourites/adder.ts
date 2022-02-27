/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import { Stop } from "@/model";
import { Favourite } from "@/store/modules/favourites";
import * as uuid from "uuid";
import { Options, Vue } from "vue-class-component";
import { State } from "vuex-class";
import { Favourites } from "@/store";
import { Watch } from "vue-property-decorator";
import WithRender from "@templates/favourites/save.html"

function createFavouriteEntry(name: string, stops: Stop[]): Favourite {
    return {
        id: uuid.v4(),
        name,
        stops
    }
}

@WithRender
@Options({ name: "FavouritesAdder" })
export class FavouritesAdder extends Vue {
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
        const errors = { name: [] };

        if (favourite.name.length == 0) {
            errors.name.push("Musisz podać nazwę.");
        }

        if (this.$store.state.favourites.favourites.filter(other => other.name == favourite.name).length > 0 && !this.confirmation) {
            errors.name.push("Istnieje już zapisana grupa przystanków o takiej nazwie.");
            this.confirmation = true;
        }

        this.errors = errors;

        return Object.entries(errors).map(a => a[1]).reduce((acc, cur) => [...acc, ...cur]).length == 0;
    }
}

export default FavouritesAdder;
