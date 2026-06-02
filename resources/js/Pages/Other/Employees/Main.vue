<script setup>
import EmployeeList from '@/Components/Lists/EmployeeList.vue';


const props = defineProps({
    employees: {
        typeof: Object
    }
})

</script>

<template>
        <div class="">
            <div>
                <h2 class="text-h6 font-weight-bold">Employee List</h2>
            </div>
            <EmployeeList :list="props.employees.data" />
        </div>
</template>