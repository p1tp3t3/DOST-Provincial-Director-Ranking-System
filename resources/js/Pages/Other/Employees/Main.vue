<script setup>
import EmployeeList from '@/Components/Lists/EmployeeList.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';


const props = defineProps({
    employees: {
        typeof: Object
    }
})

</script>

<template>
    <AuthenticatedLayout>
        <div class="">
            <div>
                <h2 class="text-h6 font-weight-bold">Employee List</h2>
            </div>
            <EmployeeList :list="props.employees.data" />
        </div>
    </AuthenticatedLayout>
</template>