import { Module } from "vuex";

const state = { };

export type RootState = typeof state;

export default <Module<RootState, unknown>>{
    state
}