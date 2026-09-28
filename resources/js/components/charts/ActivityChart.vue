<script setup lang="ts">
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Legend,
    Filler,
);

type ActivityPoint = {
    date: string;
    reviews: number | string;
    correct?: number | string;
    wrong?: number | string;
};

const props = defineProps<{
    activity: ActivityPoint[];
}>();

const { t } = useI18n();

const chartData = computed(() => ({
    labels: props.activity.map((point) => point.date),
    datasets: [
        {
            label: t('admin.reviews'),
            data: props.activity.map((point) => Number(point.reviews)),
            borderColor: 'hsl(222 47% 40%)',
            backgroundColor: 'hsla(222, 47%, 40%, 0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 3,
        },
        {
            label: t('common.correct'),
            data: props.activity.map((point) => Number(point.correct ?? 0)),
            borderColor: 'hsl(142 45% 35%)',
            backgroundColor: 'transparent',
            tension: 0.35,
            pointRadius: 2,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { precision: 0 },
        },
    },
};
</script>

<template>
    <div class="h-64 w-full">
        <Line v-if="activity.length" :data="chartData" :options="chartOptions" />
        <p
            v-else
            class="flex h-full items-center justify-center text-sm text-muted-foreground"
        >
            {{ t('admin.noActivityYet') }}
        </p>
    </div>
</template>
