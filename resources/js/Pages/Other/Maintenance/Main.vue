<template>
    <div class="maintenance-root">

        <div class="text-center">

            <!-- Logo -->
            <div class="mb-6">
                <img src="/assets/logo.png" alt="DOST" height="60"
                    style="filter:drop-shadow(0 2px 8px rgba(0,0,0,.25));" />
            </div>

            <!-- Icon -->
            <div class="icon-wrap mx-auto mb-6">
                <v-icon size="40" color="white">mdi-wrench</v-icon>
            </div>

            <!-- Text -->
            <div class="text-h5 font-weight-bold text-white mb-2">
                Under Maintenance
            </div>
            <div class="text-body-2 mb-6" style="color:rgba(255,255,255,.7); max-width:340px; margin-inline:auto;">
                We're making some improvements. The system will be back online shortly.
            </div>

            <!-- Divider -->
            <div class="divider mx-auto mb-6"></div>

            <!-- Footer note -->
            <div class="text-caption mb-6" style="color:rgba(255,255,255,.5);">
                For urgent concerns, please contact the DOST system administrator.
            </div>

            <!-- Admin link -->
            <a :href="route('console.login')" class="admin-link">Admin Access</a>

            <!-- Copyright -->
            <div class="text-caption mt-8" style="color:rgba(255,255,255,.3);">
                &copy; {{ currentYear }} Department of Science and Technology - Philippines
            </div>

        </div>

    </div>
</template>

<script setup>
const currentYear = new Date().getFullYear();
</script>

<style scoped>
.maintenance-root {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, #001f5b 0%, #0047ab 60%, #1565c0 100%);
    padding: 32px 16px;
}

.icon-wrap {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: rgba(255,255,255,.12);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2.8s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0   rgba(255,255,255,.15); }
    50%       { box-shadow: 0 0 0 16px rgba(255,255,255,0);   }
}

.divider {
    width: 40px;
    height: 2px;
    background: rgba(255,255,255,.25);
    border-radius: 2px;
}

.admin-link {
    font-size: 11px;
    color: rgba(255,255,255,.25);
    text-decoration: none;
    letter-spacing: .04em;
}

.admin-link:hover {
    color: rgba(255,255,255,.5);
}
</style>
