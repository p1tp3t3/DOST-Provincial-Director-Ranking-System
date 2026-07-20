<template>
        <AddProvinceModal v-if="user?.role == 'super_admin'" v-model:open="open" />

        <div>
            <div class="d-flex align-center justify-space-between mb-3">
                <h2 class="text-h6 font-weight-bold">Province Directories</h2>
                <v-btn
                    v-if="user?.role === 'super_admin'"
                    color="primary"
                    variant="flat"
                    size="small"
                    prepend-icon="mdi-plus"
                    @click="open = true"
                >
                    Add Province
                </v-btn>
            </div>

            <!-- Region banner for regional-level users -->
            <v-card v-if="region" class="mb-4 elevation-0 border rounded-lg overflow-hidden">
                <div class="region-banner d-flex align-center gap-3 px-5 py-3 flex-wrap">
                    <v-icon size="18" color="white">mdi-earth</v-icon>
                    <span class="region-banner-name">{{ region.name }}</span>

                    <v-divider vertical class="mx-1" style="border-color:rgba(255,255,255,0.25); height:20px;" />

                    <div class="d-flex align-center gap-2">
                        <v-avatar
                            v-if="region.regional_director"
                            :color="nameColor(region.regional_director)"
                            size="26"
                        >
                            <span style="font-size:0.6rem;font-weight:700;color:#fff;">
                                {{ initials(region.regional_director) }}
                            </span>
                        </v-avatar>
                        <div v-if="region.regional_director">
                            <div class="region-banner-label">Regional Director</div>
                            <div class="region-banner-dir">{{ region.regional_director }}</div>
                        </div>
                        <div v-else class="d-flex align-center gap-1">
                            <v-icon size="14" color="#fbbf24">mdi-account-alert-outline</v-icon>
                            <span class="region-banner-vacant">No Regional Director assigned</span>
                        </div>
                    </div>
                </div>
            </v-card>

            <ProvinceDirectoryList :list="provinces.data" />
        </div>
</template>

<script setup>
import { ref } from 'vue';
import ProvinceDirectoryList from '@/Components/Lists/ProvinceDirectoryList.vue';
import AddProvinceModal from '@/Components/Modals/Add/AddProvinceModal.vue';
import { getAuth } from '@/helper-functions';

defineProps({
    provinces: { type: Object },
    region:    { type: Object, default: null },
});

const open = ref(false);
const user = getAuth();

const palette = ['#5C6BC0','#42A5F5','#26A69A','#66BB6A','#FFA726','#EC407A','#AB47BC','#78909C'];
const nameColor = (name = '') => {
    const idx = [...name].reduce((a, c) => a + c.charCodeAt(0), 0) % palette.length;
    return palette[idx];
};
const initials = (name = '') =>
    name.split(' ').filter(Boolean).slice(0, 2).map(n => n[0]?.toUpperCase() ?? '').join('');
</script>

<style scoped>
.region-banner {
    background: linear-gradient(135deg, #1e3a6e 0%, #0f2044 100%);
    min-height: 48px;
}
.region-banner-name {
    font-size: 0.88rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.02em;
}
.region-banner-label {
    font-size: 0.62rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(255,255,255,0.5);
    line-height: 1;
}
.region-banner-dir {
    font-size: 0.82rem;
    font-weight: 500;
    color: rgba(255,255,255,0.9);
    line-height: 1.3;
}
.region-banner-vacant {
    font-size: 0.75rem;
    color: #fbbf24;
}
</style>
