<script setup lang="ts">
import jsVectorMap from 'jsvectormap';
import 'jsvectormap/dist/maps/world';
import { onMounted, ref } from 'vue';

const mapOneRef = ref<HTMLElement | null>(null);
const mapInstance = ref<any>(null);

const initMap = () => {
    if (mapOneRef.value) {
        mapInstance.value = new jsVectorMap({
            selector: mapOneRef.value,
            map: 'world',
            zoomButtons: false,
            regionStyle: {
                initial: {
                    fontFamily: 'Outfit',
                    fill: '#D9D9D9',
                },
                hover: {
                    fillOpacity: 1,
                    fill: '#465fff',
                },
            },
            markers: [
                {
                    name: 'Egypt',
                    coords: [26.8206, 30.8025],
                },
                {
                    name: 'United States',
                    coords: [55.3781, 3.436],
                },
                {
                    name: 'United States',
                    coords: [37.0902, -95.7129],
                },
            ],
            markerStyle: {
                initial: {
                    strokeWidth: 1,
                    fill: '#465fff',
                    fillOpacity: 1,
                    r: 4,
                },
                hover: {
                    fill: '#465fff',
                    fillOpacity: 1,
                },
                selected: {},
                selectedHover: {},
            },
            onRegionTooltipShow: function (event: MouseEvent, tooltip: any) {
                const code = (event.target as HTMLElement).getAttribute(
                    'data-code',
                );
                if (code === 'EG') {
                    tooltip.setContent(tooltip.text() + ' (Hello Egypt)');
                }
            },
        });
    }
};

onMounted(() => {
    initMap();
});
</script>

<template>
    <div
        class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]"
    >
        <div class="flex justify-between">
            <div>
                <h3
                    class="text-lg font-semibold text-gray-800 dark:text-white/90"
                >
                    Customers Demographic
                </h3>
                <p class="text-theme-sm mt-1 text-gray-500 dark:text-gray-400">
                    Number of customer based on country
                </p>
            </div>
        </div>
        <div
            class="border-gary-200 my-6 overflow-hidden rounded-2xl border bg-gray-50 px-4 py-6 sm:px-6 dark:border-gray-800 dark:bg-gray-900"
        >
            <div
                ref="mapOneRef"
                id="mapOne"
                class="mapOne map-btn 2xsm:w-[307px] xsm:w-[358px] -mx-4 -my-6 h-[212px] w-[252px] sm:-mx-6 md:w-[668px] lg:w-[634px] xl:w-[393px] 2xl:w-[554px]"
            ></div>
        </div>
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-full max-w-8 items-center rounded-full">
                        <img src="/images/country/country-01.svg" alt="usa" />
                    </div>
                    <div>
                        <p
                            class="text-theme-sm font-semibold text-gray-800 dark:text-white/90"
                        >
                            USA
                        </p>
                        <span
                            class="text-theme-xs block text-gray-500 dark:text-gray-400"
                        >
                            2,379 Customers
                        </span>
                    </div>
                </div>

                <div class="flex w-full max-w-[140px] items-center gap-3">
                    <div
                        class="relative block h-2 w-full max-w-[100px] rounded-sm bg-gray-200 dark:bg-gray-800"
                    >
                        <div
                            class="bg-brand-500 absolute top-0 left-0 flex h-full w-[79%] items-center justify-center rounded-sm text-xs font-medium text-white"
                        ></div>
                    </div>
                    <p
                        class="text-theme-sm font-medium text-gray-800 dark:text-white/90"
                    >
                        79%
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-full max-w-8 items-center rounded-full">
                        <img
                            src="/images/country/country-02.svg"
                            alt="france"
                        />
                    </div>
                    <div>
                        <p
                            class="text-theme-sm font-semibold text-gray-800 dark:text-white/90"
                        >
                            France
                        </p>
                        <span
                            class="text-theme-xs block text-gray-500 dark:text-gray-400"
                        >
                            589 Customers
                        </span>
                    </div>
                </div>

                <div class="flex w-full max-w-[140px] items-center gap-3">
                    <div
                        class="relative block h-2 w-full max-w-[100px] rounded-sm bg-gray-200 dark:bg-gray-800"
                    >
                        <div
                            class="bg-brand-500 absolute top-0 left-0 flex h-full w-[23%] items-center justify-center rounded-sm text-xs font-medium text-white"
                        ></div>
                    </div>
                    <p
                        class="text-theme-sm font-medium text-gray-800 dark:text-white/90"
                    >
                        23%
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
