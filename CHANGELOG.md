## [2022.3.1](https://github.com/cojedzie/cojedzie/compare/v2022.3.0...v2022.3.1) (2022-10-01)


### Bug Fixes

* **front:** Fix mobile view for stop details dialog ([8730c81](https://github.com/cojedzie/cojedzie/commit/8730c81e9e8eb40bef42a0c26d76b15a611fdcdc))



# [2022.3.0](https://github.com/cojedzie/cojedzie/compare/v2022.2.0...v2022.3.0) (2022-09-18)


### Bug Fixes

* **api:** Add import_id to schedule ([43a97fd](https://github.com/cojedzie/cojedzie/commit/43a97fd1f515bb49737fded905ad326fa8cb35b7))
* **api:** Do not include special stops in tracks ([8d5d267](https://github.com/cojedzie/cojedzie/commit/8d5d2676a9351e8752e37537bc408f1e78156d50))
* **front:** Change include-destinations to embed ([e4f7039](https://github.com/cojedzie/cojedzie/commit/e4f7039e94ff1c0d01cfd83b0785485e2c55ad89))
* **front:** Do not call API when no stops are selected ([45ee098](https://github.com/cojedzie/cojedzie/commit/45ee0987494719d7adab7c804cdfc6ca107e7d28))
* **front:** Do not use require for img src ([f3cfff6](https://github.com/cojedzie/cojedzie/commit/f3cfff6c2831b7b0aa2dd99eba7d8a77b2b00753))
* **front:** Fix storybook after upgrading to vite ([731c5bc](https://github.com/cojedzie/cojedzie/commit/731c5bc50b90f22079dda00503a608c8bb5820e3))
* **front:** Fix development server ([0048acf](https://github.com/cojedzie/cojedzie/commit/0048acf5f3121e1ad05d00a37c102aae3d40f63e))


### Features

* **api:** Add ability to bind parameters to specific arguments ([b272041](https://github.com/cojedzie/cojedzie/commit/b2720416ab980625290e22e65b068902feef4642))
* **api:** Add automatic documentation generation from parameter bindings ([0ff0985](https://github.com/cojedzie/cojedzie/commit/0ff098554317f4b3dcae316b188440fdf75450af))
* **api:** Add destination filter and stops embeding to /v1/{provider}/tracks endpoint ([7ca6973](https://github.com/cojedzie/cojedzie/commit/7ca6973c265279554e41ee6f228090103d28bc78))
* **api:** Generate documentation from parameter binding ([bccaf86](https://github.com/cojedzie/cojedzie/commit/bccaf86d81dbbac9c5e01e14b55d2c535f15ebde))
* **front:** Add button to toggle destination selection in details ([6b92137](https://github.com/cojedzie/cojedzie/commit/6b92137137adbb0cb94e3ff76749e7bdc40b01be))
* **front:** Add padding to details map so it appears vertically centered ([05eacfe](https://github.com/cojedzie/cojedzie/commit/05eacfeb2d7a3664df8b1439081fe7c84a7421b6))
* **front:** Add permanent option for UiTooltip ([a113b25](https://github.com/cojedzie/cojedzie/commit/a113b250c81126bb3123c8e9dc69569997338646))
* **front:** Introduce StopPin component ([1a42ee2](https://github.com/cojedzie/cojedzie/commit/1a42ee2eac4f2e54ab41c16e24dff142f70bbe4d))
* **front:** Invalidate map size on container size change ([1165bed](https://github.com/cojedzie/cojedzie/commit/1165bed53003abc958a68f86aefe058d57e9250a))
* **front:** New simpler look ([7aa1328](https://github.com/cojedzie/cojedzie/commit/7aa132835d0630ca321e3ca501494077b32170f3))
* **front:** New stop details dialog ([1ea579d](https://github.com/cojedzie/cojedzie/commit/1ea579d8ba3eeb96ecce40de2b67743d1c67fb3f))
* **front:** UiPin component ([179d285](https://github.com/cojedzie/cojedzie/commit/179d285c8a5b7e49656e0e440f9c897cfbfa45e4))



# [2022.2.0](https://github.com/cojedzie/cojedzie/compare/v2021.2.0...v2022.2.0) (2022-03-19)


### Bug Fixes

* **api:** Add missing index definitions ([11fafb1](https://github.com/cojedzie/cojedzie/commit/11fafb15ad2246609cce4a61dc38c98782040e67))
* **api:** Check if ConsoleOutput is available ([8ee970f](https://github.com/cojedzie/cojedzie/commit/8ee970f2d4cac987925472ef06e77507cf111073))
* **api:** Replace UuidV4Generator with UuidGenerator ([448017e](https://github.com/cojedzie/cojedzie/commit/448017e68a09e3511dcd0d29b9e05cfcaebde6b0))


### Features

* **streaming-parser:** Add ability to stop streaming json data ([1f83523](https://github.com/cojedzie/cojedzie/commit/1f83523369c675721ecd747920282e734e519263))
* **streaming-parser:** Add JSON streaming value parser ([b1bc17b](https://github.com/cojedzie/cojedzie/commit/b1bc17b3b50b0b6323cc2253f54a6a0c30a53a3b))
* **api:** Add LoggerProgressReporter ([f9f04e7](https://github.com/cojedzie/cojedzie/commit/f9f04e75af4b1e6d7feff6035fcbd6b51a3804c8))
* **api:** Add MilestoneType to progress reporters ([1c18a87](https://github.com/cojedzie/cojedzie/commit/1c18a874ffeb39441ebc937d5a688fd927831bbe))
* **api:** Add mysql support ([c126f10](https://github.com/cojedzie/cojedzie/commit/c126f10576d0c811ba855ced9699a0cb4196eac8))
* **api:** Add new progress reporting interface ([ad90c2a](https://github.com/cojedzie/cojedzie/commit/ad90c2add9ee3e62e3d0e1e3a549dcf7b16dac40))
* **api:** Add support for PostgreSQL ([2a5cc85](https://github.com/cojedzie/cojedzie/commit/2a5cc85e495be381fb47be60a5a5917e665ff36f))
* **api:** Associate data with import events ([c2e5e48](https://github.com/cojedzie/cojedzie/commit/c2e5e484b93e8dedde827bcb25ed1c92e2818917))


### Performance Improvements

* **streaming-parser:** Introduce FileStringStream to further optimize parsing ([ba3cf19](https://github.com/cojedzie/cojedzie/commit/ba3cf197af7cda3c4a971d7eda5751bb0714e36e))
* **streaming-parser:** Optimize streaming parser by introducing non-streaming parsers ([bfe17cf](https://github.com/cojedzie/cojedzie/commit/bfe17cfd4a86e459d02bd9c1c0f44cc68ba78a8f))
* **streaming-parser:** Use Anonymous classes instead of callbacks ([6ab5a17](https://github.com/cojedzie/cojedzie/commit/6ab5a17a5351a4341ce0ec077c678642afb75e36))
* **api:** Enable JIT ([f7f2e68](https://github.com/cojedzie/cojedzie/commit/f7f2e685048b0c699276b4e7673182238e025ec5))



# [2021.2.0](https://github.com/cojedzie/cojedzie/compare/v2021.1.2...v2021.2.0) (2021-11-27)


### Bug Fixes

* **front:** Fix copying assets ([00c35fb](https://github.com/cojedzie/cojedzie/commit/00c35fb714186dd00c40d7c9af4feae888cab0d0))
* **front:** Fix ui-fold edge cases and overflow ([5c43f08](https://github.com/cojedzie/cojedzie/commit/5c43f08c5dcf1a17770c75ddb58b1b5841c2dc55))
* **front:** Update ui-numeric-input for vue 3 v-model ([cffb4cc](https://github.com/cojedzie/cojedzie/commit/cffb4ccbd7f2a66a63049d7fbd63f7c2c0e0707f))
* **front:** Update ui-switch to be vue 3 v-model compatible ([0938e79](https://github.com/cojedzie/cojedzie/commit/0938e7901a3749c734b7ab668f482eb58a999481))


### Features

* **front:** Add ability to fine control relative time display ([10ba129](https://github.com/cojedzie/cojedzie/commit/10ba1291232bd33f406c75ae4fb4aa7406dbbbbf))
* **front:** Add help to all settings ([6f63830](https://github.com/cojedzie/cojedzie/commit/6f638303a3ae58926971900e9e3017b045d3ee25))
* **front:** Add mutex for components ([729d1ff](https://github.com/cojedzie/cojedzie/commit/729d1fff654b031d3085de06534a3961fd050c38))
* **front:** Add ui-help component ([6eeb693](https://github.com/cojedzie/cojedzie/commit/6eeb6935ae55077a5307bc7b426ae49129286446))


### Performance Improvements

* **front:** Lazy load map js via ui-map component ([f13c089](https://github.com/cojedzie/cojedzie/commit/f13c08905996acf6af369829d5a473c1ab44d2ee))
* **front:** Reduce unused js in bundles ([6f7327d](https://github.com/cojedzie/cojedzie/commit/6f7327dd2597ad025fa5a4e1826eb523843d3e31))



## [2021.1.2](https://github.com/cojedzie/cojedzie/compare/v2021.1.1...v2021.1.2) (2021-06-05)



## [2021.1.1](https://github.com/cojedzie/cojedzie/compare/v2021.1.0...v2021.1.1) (2021-06-03)



# 2021.1.0 (2021-06-03)



