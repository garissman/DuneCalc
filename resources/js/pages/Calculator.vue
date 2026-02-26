<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { evaluate } from 'mathjs';
import { computed, ref, watch } from 'vue';
import {
    store,
    update,
    destroy,
    destroyAll,
} from '@/actions/App/Http/Controllers/CalculationController';
import type { Calculation } from '@/types';

const props = defineProps<{ calculations: Calculation[] }>();

/** The raw expression string currently in the input field. */
const expression = ref('');

/** Evaluated numeric result of the current expression, or null if empty/invalid. */
const liveResult = ref<number | null>(null);

/** Error message shown below the input when the expression cannot be evaluated. */
const liveError = ref<string | null>(null);

/** ID of the calculation currently being edited, or null when in create mode. */
const editingId = ref<number | null>(null);

/** Inertia form used to POST new calculations. */
const storeForm = useForm({ expression: '', result: 0 });

/** Inertia form used to PUT updates to an existing calculation. */
const updateForm = useForm({ expression: '', result: 0 });

/** True when the inline "Are you sure?" confirmation is visible for Clear All. */
const confirmingClearAll = ref(false);

/** True when a calculation is loaded into the input for editing. */
const isEditing = computed(() => editingId.value !== null);

/** ID of the calculation pending single-item delete confirmation, or null when no confirmation is open. */
const deletingId = ref<number | null>(null);

/** True when the expression is non-empty and evaluates without error. */
const isExpressionValid = computed(
    () =>
        liveError.value === null &&
        liveResult.value !== null &&
        expression.value.trim() !== '',
);

/**
 * Watches the expression input and evaluates it live using mathjs.
 * Sets liveResult on success, or liveError on invalid syntax or division by zero.
 */
watch(expression, (value) => {
    const trimmed = value.trim();

    if (trimmed === '') {
        liveResult.value = null;
        liveError.value = null;
        return;
    }

    try {
        const evaluated = evaluate(trimmed);

        if (typeof evaluated !== 'number') {
            liveResult.value = null;
            liveError.value = 'Invalid expression';
        } else if (!isFinite(evaluated)) {
            liveResult.value = null;
            liveError.value = 'Cannot divide by zero';
        } else {
            liveResult.value = evaluated;
            liveError.value = null;
        }
    } catch {
        liveResult.value = null;
        liveError.value = 'Invalid expression';
    }
});

/**
 * Appends an operator or function token to the expression input.
 * @param value - The string to append (e.g. '+', 'sqrt(', 'pi').
 */
function appendToExpression(value: string): void {
    expression.value += value;
}

/**
 * Clears the expression input and resets all live-preview and edit state.
 */
function clearExpression(): void {
    expression.value = '';
    liveResult.value = null;
    liveError.value = null;
    editingId.value = null;
}

/**
 * Enters edit mode for the given calculation, loading its expression into the input.
 * @param calculation - The calculation record to edit.
 */
function startEditing(calculation: Calculation): void {
    editingId.value = calculation.id;
    expression.value = calculation.expression;
}

/**
 * Exits edit mode and clears the input, discarding any unsaved changes.
 * Kept as a named function (rather than calling clearExpression directly) so the
 * template communicates intent, and to allow future logic (e.g. unsaved-changes guard).
 */
function cancelEditing(): void {
    clearExpression();
}

/**
 * Submits the current expression.
 * - In create mode: POSTs a new calculation via storeForm.
 * - In edit mode: PUTs an update to the existing calculation via updateForm.
 * No-op if the expression is empty or invalid.
 */
function submitCalculation(): void {
    if (!isExpressionValid.value || liveResult.value === null) {
        return;
    }

    if (isEditing.value && editingId.value !== null) {
        updateForm.expression = expression.value;
        updateForm.result = liveResult.value;
        updateForm.put(update({ calculation: editingId.value }).url, {
            preserveScroll: true,
            onSuccess: clearExpression,
        });
    } else {
        storeForm.expression = expression.value;
        storeForm.result = liveResult.value;
        storeForm.post(store().url, {
            preserveScroll: true,
            onSuccess: clearExpression,
        });
    }
}

/**
 * Shows the inline confirmation UI for deleting a single calculation.
 * @param id - The ID of the calculation to request deletion for.
 */
function requestDeleteCalculation(id: number): void {
    deletingId.value = id;
}

/**
 * Cancels the pending single-item delete confirmation without deleting anything.
 */
function cancelDeleteCalculation(): void {
    deletingId.value = null;
}

/**
 * Sends a DELETE request for the calculation currently pending confirmation.
 */
function confirmDeleteCalculation(): void {
    router.delete(destroy({ calculation: deletingId.value! }).url, {
        preserveScroll: true,
    });
    deletingId.value = null;
}

/**
 * Shows the inline confirmation UI for clearing all history.
 */
function requestClearAll(): void {
    confirmingClearAll.value = true;
}

/**
 * Cancels the pending Clear All confirmation without deleting anything.
 */
function cancelClearAll(): void {
    confirmingClearAll.value = false;
}

/**
 * Sends a DELETE request to remove all calculations for the current session.
 */
function clearAllCalculations(): void {
    confirmingClearAll.value = false;
    router.delete(destroyAll().url, { preserveScroll: true });
}

/**
 * Routes a keypad button click: 'backspace' removes the last character; all other values append.
 * @param value - The button value string.
 */
function handleButton(value: string): void {
    if (value === 'backspace') {
        backspace();
    } else {
        appendToExpression(value);
    }
}

/**
 * Removes the last character from the expression input. No-op when empty.
 */
function backspace(): void {
    expression.value = expression.value.slice(0, -1);
}

/** Function-row buttons rendered above the main keypad. */
const functionButtons = [
    { label: '√', value: 'sqrt(', ariaLabel: 'Square root' },
    { label: '^', value: '^', ariaLabel: 'Power' },
    { label: 'π', value: 'pi', ariaLabel: 'Pi' },
    { label: '(', value: '(', ariaLabel: 'Open parenthesis' },
    { label: ')', value: ')', ariaLabel: 'Close parenthesis' },
] as const;

/** Main 4×4 keypad buttons in row-major order. */
const mainButtons = [
    { label: '7', value: '7' },
    { label: '8', value: '8' },
    { label: '9', value: '9' },
    { label: '÷', value: '/' },
    { label: '4', value: '4' },
    { label: '5', value: '5' },
    { label: '6', value: '6' },
    { label: '×', value: '*' },
    { label: '1', value: '1' },
    { label: '2', value: '2' },
    { label: '3', value: '3' },
    { label: '−', value: '-' },
    { label: '0', value: '0' },
    { label: '.', value: '.' },
    { label: '⌫', value: 'backspace' },
    { label: '+', value: '+' },
] as const;
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-gray-950 px-4 py-10 text-gray-100"
    >
        <Head title="CalcTek" />

        <div
            class="w-full max-w-3xl overflow-hidden rounded-2xl bg-gray-900 shadow-2xl"
        >
            <!-- Brand header -->
            <div class="border-b border-gray-800 px-6 py-3">
                <h1
                    class="font-mono text-sm font-bold tracking-widest text-gray-400 uppercase"
                >
                    CalcTek
                </h1>
            </div>

            <div class="flex flex-col md:flex-row">
                <!-- Left panel: Calculator -->
                <div class="flex flex-1 flex-col gap-3 p-5">
                    <!-- LCD display area -->
                    <div
                        class="relative flex min-h-20 flex-col justify-end gap-1 rounded-lg bg-gray-950 px-4 py-3"
                    >
                        <span
                            v-if="isEditing"
                            class="absolute top-2 left-3 rounded border border-indigo-500 px-1.5 py-0.5 font-mono text-xs text-indigo-400"
                        >
                            Editing
                        </span>
                        <input
                            v-model="expression"
                            type="text"
                            aria-label="Expression input"
                            placeholder="Enter expression…"
                            maxlength="500"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="none"
                            spellcheck="false"
                            class="w-full bg-transparent text-right font-mono text-2xl text-white placeholder-gray-700 focus:outline-none"
                            @keydown.enter="submitCalculation"
                        />
                        <div aria-live="polite">
                            <p
                                v-if="liveError"
                                class="text-right font-mono text-sm text-red-400"
                            >
                                {{ liveError }}
                            </p>
                            <p
                                v-else-if="liveResult !== null"
                                class="text-right font-mono text-sm text-indigo-400"
                            >
                                = {{ liveResult }}
                            </p>
                            <p
                                v-else
                                class="text-right font-mono text-sm text-gray-700"
                            >
                                &nbsp;
                            </p>
                        </div>
                    </div>

                    <!-- Function row: √ ^ π ( ) -->
                    <div class="grid grid-cols-5 gap-1.5">
                        <button
                            v-for="btn in functionButtons"
                            :key="btn.value"
                            type="button"
                            :aria-label="btn.ariaLabel"
                            class="rounded-xl bg-gray-700 py-2 font-mono text-xs text-gray-300 transition-all hover:brightness-110 active:scale-95"
                            @click="appendToExpression(btn.value)"
                        >
                            {{ btn.label }}
                        </button>
                    </div>

                    <!-- Main 4×4 keypad -->
                    <div class="grid grid-cols-4 gap-1.5">
                        <button
                            v-for="btn in mainButtons"
                            :key="btn.value"
                            type="button"
                            :aria-label="
                                btn.label === '⌫' ? 'Backspace' : btn.label
                            "
                            :class="[
                                'rounded-xl py-3 font-mono text-lg transition-all hover:brightness-110 active:scale-95',
                                ['÷', '×', '−', '+'].includes(btn.label)
                                    ? 'bg-gray-700 text-indigo-300'
                                    : btn.label === '⌫'
                                      ? 'bg-gray-700 text-red-300'
                                      : 'bg-gray-800 text-white',
                            ]"
                            @click="handleButton(btn.value)"
                        >
                            {{ btn.label }}
                        </button>
                    </div>

                    <!-- Bottom row: C + (Cancel in edit mode) + = / Update -->
                    <div
                        class="grid gap-1.5"
                        :class="isEditing ? 'grid-cols-3' : 'grid-cols-2'"
                    >
                        <button
                            type="button"
                            aria-label="Clear"
                            class="rounded-xl bg-red-900 py-3 font-mono text-lg text-red-200 transition-all hover:bg-red-800 active:scale-95"
                            @click="clearExpression"
                        >
                            C
                        </button>
                        <button
                            v-if="isEditing"
                            type="button"
                            aria-label="Cancel editing"
                            class="rounded-xl border border-gray-600 py-3 text-sm text-gray-400 transition-all hover:border-gray-500 hover:text-gray-200 active:scale-95"
                            @click="cancelEditing"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :aria-label="
                                isEditing
                                    ? 'Update calculation'
                                    : 'Calculate result'
                            "
                            :disabled="
                                !isExpressionValid ||
                                storeForm.processing ||
                                updateForm.processing
                            "
                            class="rounded-xl bg-indigo-600 py-3 font-mono text-lg font-bold text-white transition-all hover:bg-indigo-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                            @click="submitCalculation"
                        >
                            {{ isEditing ? 'Update' : '=' }}
                        </button>
                    </div>
                </div>

                <!-- Divider: vertical on md+, horizontal on mobile -->
                <div
                    class="my-4 hidden border-l border-gray-700 md:block"
                ></div>
                <div
                    class="mx-4 block border-t border-gray-700 md:hidden"
                ></div>

                <!-- Right panel: History tape -->
                <div class="flex w-full shrink-0 flex-col p-5 md:w-80">
                    <!-- Header -->
                    <div class="mb-3 flex items-center justify-between">
                        <h2
                            class="text-xs font-semibold tracking-widest text-gray-400 uppercase"
                        >
                            History
                        </h2>
                        <div
                            v-if="props.calculations.length > 0"
                            class="flex items-center gap-1.5"
                        >
                            <template v-if="confirmingClearAll">
                                <span class="text-xs text-gray-400">Sure?</span>
                                <button
                                    type="button"
                                    aria-label="Confirm clear all history"
                                    class="rounded border border-red-700 px-2 py-0.5 text-xs text-red-400 transition-all hover:border-red-500 hover:text-red-300 active:scale-95"
                                    @click="clearAllCalculations"
                                >
                                    Yes
                                </button>
                                <button
                                    type="button"
                                    aria-label="Cancel clear all"
                                    class="rounded border border-gray-600 px-2 py-0.5 text-xs text-gray-400 transition-all hover:border-gray-500 hover:text-gray-200 active:scale-95"
                                    @click="cancelClearAll"
                                >
                                    No
                                </button>
                            </template>
                            <button
                                v-else
                                type="button"
                                aria-label="Clear all history"
                                class="rounded border border-red-800 px-2 py-0.5 text-xs text-red-400 transition-all hover:border-red-600 hover:text-red-300 active:scale-95"
                                @click="requestClearAll"
                            >
                                Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <p
                        v-if="props.calculations.length === 0"
                        class="py-10 text-center text-sm text-gray-500"
                    >
                        No history yet.
                    </p>

                    <!-- Calculation list -->
                    <ul
                        v-else
                        class="flex max-h-[420px] flex-col gap-1.5 overflow-y-auto pr-1"
                    >
                        <li
                            v-for="calculation in props.calculations"
                            :key="calculation.id"
                            :class="[
                                'flex items-start justify-between rounded-lg bg-gray-800 px-3 py-2.5',
                                editingId === calculation.id
                                    ? 'border-l-2 border-indigo-500'
                                    : '',
                            ]"
                        >
                            <div class="flex min-w-0 flex-col gap-0.5">
                                <span
                                    class="truncate font-mono text-sm text-gray-300"
                                    >{{ calculation.expression }}</span
                                >
                                <span
                                    class="font-mono text-base font-semibold text-indigo-400"
                                    >= {{ calculation.result }}</span
                                >
                            </div>
                            <div class="ml-2 flex shrink-0 items-center gap-1">
                                <button
                                    type="button"
                                    :aria-label="`Edit ${calculation.expression}`"
                                    class="rounded px-1.5 py-1 text-xs text-gray-400 transition-all hover:bg-gray-700 hover:text-white active:scale-95"
                                    @click="startEditing(calculation)"
                                >
                                    ✎
                                </button>
                                <template v-if="deletingId === calculation.id">
                                    <span class="text-xs text-gray-400"
                                        >Sure?</span
                                    >
                                    <button
                                        type="button"
                                        :aria-label="`Confirm delete ${calculation.expression}`"
                                        class="rounded border border-red-700 px-2 py-0.5 text-xs text-red-400 transition-all hover:border-red-500 hover:text-red-300 active:scale-95"
                                        @click="confirmDeleteCalculation"
                                    >
                                        Yes
                                    </button>
                                    <button
                                        type="button"
                                        :aria-label="`Cancel delete ${calculation.expression}`"
                                        class="rounded border border-gray-600 px-2 py-0.5 text-xs text-gray-400 transition-all hover:border-gray-500 hover:text-gray-200 active:scale-95"
                                        @click="cancelDeleteCalculation"
                                    >
                                        No
                                    </button>
                                </template>
                                <button
                                    v-else
                                    type="button"
                                    :aria-label="`Delete ${calculation.expression}`"
                                    class="rounded px-1.5 py-1 text-xs text-red-500 transition-all hover:bg-gray-700 hover:text-red-400 active:scale-95"
                                    @click="
                                        requestDeleteCalculation(calculation.id)
                                    "
                                >
                                    ✕
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
