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

const { t, locale } = useI18n();

const sortedActivity = computed(() =>
    [...props.activity].sort((a, b) => a.date.localeCompare(b.date)),
);

function formatDateLabel(value: string): string {
    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(locale.value, {
        day: 'numeric',
        month: 'short',
    });
}

const chartData = computed(() => ({
    labels: sortedActivity.value.map((point) => formatDateLabel(point.date)),
    datasets: [
        {
            label: t('admin.reviews'),
            data: sortedActivity.value.map((point) => Number(point.reviews)),
            borderColor: 'hsl(222 47% 40%)',
            backgroundColor: 'hsla(222, 47%, 40%, 0.12)',
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
        {
            label: t('common.correct'),
            data: sortedActivity.value.map((point) =>
                Number(point.correct ?? 0),
            ),
            borderColor: 'hsl(142 45% 35%)',
            backgroundColor: 'transparent',
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 5,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
        },
        tooltip: {
            callbacks: {
                title: (items: Array<{ dataIndex: number }>): string => {
                    const point = sortedActivity.value[items[0]?.dataIndex];

                    if (!point) {
                        return '';
                    }

                    const date = new Date(`${point.date}T00:00:00`);

                    if (Number.isNaN(date.getTime())) {
                        return point.date;
                    }

                    return date.toLocaleDateString(locale.value, {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                    });
                },
            },
        },
    },
    scales: {
        x: {
            title: {
                display: true,
                text: t('admin.chartDateAxis'),
            },
            ticks: {
                maxRotation: 45,
                minRotation: 0,
                autoSkip: true,
            },
        },
        y: {
            beginAtZero: true,
            title: {
                display: true,
                text: t('admin.chartCountAxis'),
            },
            ticks: { precision: 0 },
        },
    },
}));
</script>

<template>
    <div class="h-64 w-full">
        <Line
            v-if="sortedActivity.length"
            :data="chartData"
            :options="chartOptions"
        />
        <p
            v-else
            class="flex h-full items-center justify-center text-sm text-muted-foreground"
        >
            {{ t('admin.noActivityYet') }}
        </p>
    </div>
</template>
