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

import { Options, Vue } from "vue-class-component";
import { Favourites } from "@/store";
import { Favourite } from "@/store/modules/favourites";
import { Mutation } from "vuex-class";
import { Stop } from "@/model";
import WithRender from "@templates/favourites/list.html"

@WithRender
@Options({ name: "FavouritesList" })
export class FavouritesList extends Vue {
    @Favourites.State favourites: Favourite[];
    @Favourites.Mutation remove: (fav: Favourite) => void;
    @Mutation('replace') setStops: (stops: Stop[]) => void;

    choose(favourite: Favourite) {
        this.setStops(favourite.stops);
    }
}

export default FavouritesList;
