import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

export const getAuth = () => {
    const page = usePage();
    
    // 💡 Wrap it in a computed property so it stays reactive when data updates
    const user = computed(() => page.props.auth?.user ?? null);
    
    return user.value;
};


export const receiveBroadcast = (channel, type, event, callback) => {
    if(type == 'private') {
        window.Echo.private(channel).listen(event, callback);
    }
    if(type == 'public') {
        window.Echo.channel(channel).listen(event, callback);
    }
}