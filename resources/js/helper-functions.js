import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

export const getAuth = () => {
    const page = usePage();
    
    // 💡 Wrap it in a computed property so it stays reactive when data updates
    const user = computed(() => page.props.auth?.user ?? null);
    
    return user.value;
};
