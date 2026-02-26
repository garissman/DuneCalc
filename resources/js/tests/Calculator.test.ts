import type { InertiaForm } from '@inertiajs/vue3';
import { router, useForm } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import {
    store,
    update,
    destroy,
    destroyAll,
} from '@/actions/App/Http/Controllers/CalculationController';
import Calculator from '@/pages/Calculator.vue';
import type { Calculation } from '@/types';

// Mock Inertia Head as a no-op component
vi.mock('@inertiajs/vue3', () => {
    const postMock = vi.fn();
    const putMock = vi.fn();

    const makeForm = () => ({
        expression: '',
        result: 0,
        post: postMock,
        put: putMock,
    });

    return {
        Head: { template: '<slot />' },
        router: {
            delete: vi.fn(),
        },
        useForm: vi.fn(() => makeForm()),
    };
});

// Mock Wayfinder CalculationController
vi.mock('@/actions/App/Http/Controllers/CalculationController', () => ({
    store: vi.fn(() => ({ url: '/calculations', method: 'post' })),
    update: vi.fn(({ calculation }) => ({
        url: `/calculations/${calculation}`,
        method: 'put',
    })),
    destroy: vi.fn(({ calculation }) => ({
        url: `/calculations/${calculation}`,
        method: 'delete',
    })),
    destroyAll: vi.fn(() => ({ url: '/calculations', method: 'delete' })),
}));

const sampleCalculations: Calculation[] = [
    {
        id: 1,
        expression: '2 + 2',
        result: 4,
        created_at: '2026-01-01',
        updated_at: '2026-01-01',
    },
    {
        id: 2,
        expression: '10 / 2',
        result: 5,
        created_at: '2026-01-01',
        updated_at: '2026-01-01',
    },
];

function mountCalculator(calculations: Calculation[] = []) {
    return mount(Calculator, {
        props: { calculations },
        global: {
            stubs: {
                Head: true,
            },
        },
    });
}

describe('Calculator.vue', () => {
    beforeEach(() => {
        vi.clearAllMocks();

        // Reset useForm to return fresh mock form objects each call
        vi.mocked(useForm).mockImplementation(
            () =>
                ({
                    expression: '',
                    result: 0,
                    post: vi.fn(),
                    put: vi.fn(),
                }) as unknown as InertiaForm<{
                    expression: string;
                    result: number;
                }>,
        );
    });

    it('renders without crashing and shows the CalcTek heading', () => {
        const wrapper = mountCalculator();
        expect(wrapper.find('h1').text()).toBe('CalcTek');
    });

    it('shows "No history yet." when history is empty', () => {
        const wrapper = mountCalculator([]);
        expect(wrapper.text()).toContain('No history yet.');
    });

    it('does not show the empty state when calculations exist', () => {
        const wrapper = mountCalculator(sampleCalculations);
        expect(wrapper.text()).not.toContain('No history yet.');
    });

    it('renders calculations in the ticker tape history', () => {
        const wrapper = mountCalculator(sampleCalculations);
        expect(wrapper.text()).toContain('2 + 2');
        expect(wrapper.text()).toContain('10 / 2');
    });

    it('shows live result for a valid expression', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('2 + 2');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('= 4');
    });

    it('shows an error for an invalid expression', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('((invalid');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Invalid expression');
    });

    it('shows "Cannot divide by zero" for division by zero', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('1/0');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Cannot divide by zero');
    });

    it('shows "Invalid expression" for complex number results like 2i', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('2i');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Invalid expression');
        expect(wrapper.text()).not.toContain('Cannot divide by zero');
    });

    it('shows "Invalid expression" for sqrt of negative number', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('sqrt(-1)');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Invalid expression');
        expect(wrapper.text()).not.toContain('Cannot divide by zero');
    });

    it('submit button is disabled when expression is invalid', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('((invalid');
        await wrapper.vm.$nextTick();
        const submitBtn = wrapper.find('button[disabled]');
        expect(submitBtn.exists()).toBe(true);
    });

    it('submit button is disabled when expression is empty', async () => {
        const wrapper = mountCalculator();
        const submitBtn = wrapper.find('button[disabled]');
        expect(submitBtn.exists()).toBe(true);
    });

    it('submit button is enabled when expression is valid', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('5 * 5');
        await wrapper.vm.$nextTick();
        const submitBtn = wrapper.find('button:not([disabled])');
        expect(submitBtn.exists()).toBe(true);
    });

    it('appends operator to expression when "+" helper button is clicked', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('5');
        await wrapper.vm.$nextTick();

        // Find the + helper button (label "+" maps to value "+")
        const buttons = wrapper.findAll('button[type="button"]');
        const plusButton = buttons.find((b) => b.text() === '+');
        expect(plusButton).toBeDefined();
        await plusButton!.trigger('click');
        await wrapper.vm.$nextTick();

        expect((input.element as HTMLInputElement).value).toBe('5+');
    });

    it('appends subtract operator via "−" helper button', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('10');
        await wrapper.vm.$nextTick();

        const buttons = wrapper.findAll('button[type="button"]');
        const minusButton = buttons.find((b) => b.text() === '−');
        expect(minusButton).toBeDefined();
        await minusButton!.trigger('click');
        await wrapper.vm.$nextTick();

        expect((input.element as HTMLInputElement).value).toBe('10-');
    });

    it('appends multiply operator via "×" helper button', async () => {
        const wrapper = mountCalculator();
        const buttons = wrapper.findAll('button[type="button"]');
        const multiplyButton = buttons.find((b) => b.text() === '×');
        expect(multiplyButton).toBeDefined();
        await multiplyButton!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(
            (wrapper.find('input[type="text"]').element as HTMLInputElement)
                .value,
        ).toBe('*');
    });

    it('appends divide operator via "÷" helper button', async () => {
        const wrapper = mountCalculator();
        const buttons = wrapper.findAll('button[type="button"]');
        const divideButton = buttons.find((b) => b.text() === '÷');
        expect(divideButton).toBeDefined();
        await divideButton!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(
            (wrapper.find('input[type="text"]').element as HTMLInputElement)
                .value,
        ).toBe('/');
    });

    it('appends "(" via helper button', async () => {
        const wrapper = mountCalculator();
        const buttons = wrapper.findAll('button[type="button"]');
        const parenButton = buttons.find((b) => b.text() === '(');
        expect(parenButton).toBeDefined();
        await parenButton!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(
            (wrapper.find('input[type="text"]').element as HTMLInputElement)
                .value,
        ).toBe('(');
    });

    it('appends ")" via helper button', async () => {
        const wrapper = mountCalculator();
        const buttons = wrapper.findAll('button[type="button"]');
        const parenButton = buttons.find((b) => b.text() === ')');
        expect(parenButton).toBeDefined();
        await parenButton!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(
            (wrapper.find('input[type="text"]').element as HTMLInputElement)
                .value,
        ).toBe(')');
    });

    it('appends "^" via helper button', async () => {
        const wrapper = mountCalculator();
        const buttons = wrapper.findAll('button[type="button"]');
        const caretButton = buttons.find((b) => b.text() === '^');
        expect(caretButton).toBeDefined();
        await caretButton!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(
            (wrapper.find('input[type="text"]').element as HTMLInputElement)
                .value,
        ).toBe('^');
    });

    it('appends "sqrt(" via "√" function button', async () => {
        const wrapper = mountCalculator();
        const buttons = wrapper.findAll('button[type="button"]');
        const sqrtButton = buttons.find((b) => b.text() === '√');
        expect(sqrtButton).toBeDefined();
        await sqrtButton!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(
            (wrapper.find('input[type="text"]').element as HTMLInputElement)
                .value,
        ).toBe('sqrt(');
    });

    it('appends "pi" via "π" helper button', async () => {
        const wrapper = mountCalculator();
        const buttons = wrapper.findAll('button[type="button"]');
        const piButton = buttons.find((b) => b.text() === 'π');
        expect(piButton).toBeDefined();
        await piButton!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(
            (wrapper.find('input[type="text"]').element as HTMLInputElement)
                .value,
        ).toBe('pi');
    });

    it('appends digit "7" via keypad button', async () => {
        const wrapper = mountCalculator();
        const buttons = wrapper.findAll('button[type="button"]');
        const sevenButton = buttons.find((b) => b.text() === '7');
        expect(sevenButton).toBeDefined();
        await sevenButton!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(
            (wrapper.find('input[type="text"]').element as HTMLInputElement)
                .value,
        ).toBe('7');
    });

    it('appends "." via keypad button', async () => {
        const wrapper = mountCalculator();
        const buttons = wrapper.findAll('button[type="button"]');
        const dotButton = buttons.find((b) => b.text() === '.');
        expect(dotButton).toBeDefined();
        await dotButton!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(
            (wrapper.find('input[type="text"]').element as HTMLInputElement)
                .value,
        ).toBe('.');
    });

    it('removes the last character when "⌫" is clicked', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('12');
        await wrapper.vm.$nextTick();

        const buttons = wrapper.findAll('button[type="button"]');
        const backspaceButton = buttons.find((b) => b.text() === '⌫');
        expect(backspaceButton).toBeDefined();
        await backspaceButton!.trigger('click');
        await wrapper.vm.$nextTick();

        expect((input.element as HTMLInputElement).value).toBe('1');
    });

    it('does nothing when "⌫" is clicked on an empty expression', async () => {
        const wrapper = mountCalculator();
        const buttons = wrapper.findAll('button[type="button"]');
        const backspaceButton = buttons.find((b) => b.text() === '⌫');
        expect(backspaceButton).toBeDefined();
        await backspaceButton!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(
            (wrapper.find('input[type="text"]').element as HTMLInputElement)
                .value,
        ).toBe('');
    });

    it('clears expression with the "C" button', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('2 + 2');
        await wrapper.vm.$nextTick();

        const buttons = wrapper.findAll('button[type="button"]');
        const clearButton = buttons.find((b) => b.text() === 'C');
        expect(clearButton).toBeDefined();
        await clearButton!.trigger('click');
        await wrapper.vm.$nextTick();

        expect((input.element as HTMLInputElement).value).toBe('');
        expect(wrapper.text()).not.toContain('= 4');
    });

    it('evaluates sqrt(9) to 3', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('sqrt(9)');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('= 3');
    });

    it('evaluates 2^3 to 8', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('2^3');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('= 8');
    });

    it('evaluates pi to approximately 3.14', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('pi');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('= 3.14159');
    });

    it('shows "Clear All" button when calculations exist', () => {
        const wrapper = mountCalculator(sampleCalculations);
        const buttons = wrapper.findAll('button[type="button"]');
        const clearAllBtn = buttons.find((b) => b.text() === 'Clear All');
        expect(clearAllBtn).toBeDefined();
        expect(clearAllBtn!.exists()).toBe(true);
    });

    it('does not show "Clear All" button when history is empty', () => {
        const wrapper = mountCalculator([]);
        const buttons = wrapper.findAll('button[type="button"]');
        const clearAllBtn = buttons.find((b) => b.text() === 'Clear All');
        expect(clearAllBtn).toBeUndefined();
    });

    it('shows inline "Sure? Yes No" confirmation when "Clear All" is clicked', async () => {
        const wrapper = mountCalculator(sampleCalculations);
        const clearAllBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'Clear All');
        await clearAllBtn!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Sure?');
        expect(
            wrapper
                .findAll('button[type="button"]')
                .find((b) => b.text() === 'Yes'),
        ).toBeDefined();
        expect(
            wrapper
                .findAll('button[type="button"]')
                .find((b) => b.text() === 'No'),
        ).toBeDefined();
    });

    it('calls router.delete with destroyAll url when "Yes" is clicked after "Clear All"', async () => {
        const wrapper = mountCalculator(sampleCalculations);
        const clearAllBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'Clear All');
        await clearAllBtn!.trigger('click');
        await wrapper.vm.$nextTick();
        const yesBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'Yes');
        await yesBtn!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(destroyAll).toHaveBeenCalled();
        expect(vi.mocked(router.delete)).toHaveBeenCalledWith('/calculations', {
            preserveScroll: true,
        });
    });

    it('does not delete and hides confirmation when "No" is clicked', async () => {
        const wrapper = mountCalculator(sampleCalculations);
        const clearAllBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'Clear All');
        await clearAllBtn!.trigger('click');
        await wrapper.vm.$nextTick();
        const noBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'No');
        await noBtn!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(destroyAll).not.toHaveBeenCalled();
        expect(vi.mocked(router.delete)).not.toHaveBeenCalled();
        expect(wrapper.text()).not.toContain('Sure?');
    });

    it('shows inline "Sure? Yes No" confirmation when "✕" is clicked on an item', async () => {
        const wrapper = mountCalculator(sampleCalculations);
        const deleteBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === '✕');
        await deleteBtn!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Sure?');
        expect(
            wrapper
                .findAll('button[type="button"]')
                .find((b) => b.text() === 'Yes'),
        ).toBeDefined();
        expect(
            wrapper
                .findAll('button[type="button"]')
                .find((b) => b.text() === 'No'),
        ).toBeDefined();
    });

    it('calls router.delete with destroy url when "Yes" is clicked after "✕" confirmation', async () => {
        const wrapper = mountCalculator(sampleCalculations);
        const deleteBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === '✕');
        await deleteBtn!.trigger('click');
        await wrapper.vm.$nextTick();
        const yesBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'Yes');
        await yesBtn!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(destroy).toHaveBeenCalledWith({ calculation: 1 });
        expect(vi.mocked(router.delete)).toHaveBeenCalledWith(
            '/calculations/1',
            { preserveScroll: true },
        );
    });

    it('does not delete and hides confirmation when "No" is clicked on item delete', async () => {
        const wrapper = mountCalculator(sampleCalculations);
        const deleteBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === '✕');
        await deleteBtn!.trigger('click');
        await wrapper.vm.$nextTick();
        const noBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'No');
        await noBtn!.trigger('click');
        await wrapper.vm.$nextTick();
        expect(destroy).not.toHaveBeenCalled();
        expect(vi.mocked(router.delete)).not.toHaveBeenCalled();
        expect(wrapper.text()).not.toContain('Sure?');
    });

    it('shows "✎" buttons for each calculation in history', () => {
        const wrapper = mountCalculator(sampleCalculations);
        const buttons = wrapper.findAll('button[type="button"]');
        const editButtons = buttons.filter((b) => b.text() === '✎');
        expect(editButtons.length).toBe(2);
    });

    it('enters edit mode when "✎" is clicked, showing Update and Cancel buttons', async () => {
        const wrapper = mountCalculator(sampleCalculations);
        const buttons = wrapper.findAll('button[type="button"]');
        const editBtn = buttons.find((b) => b.text() === '✎');
        await editBtn!.trigger('click');
        await wrapper.vm.$nextTick();

        // Submit button should now say "Update"
        expect(wrapper.text()).toContain('Update');
        // Cancel button should appear
        const cancelBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'Cancel');
        expect(cancelBtn).toBeDefined();
        expect(cancelBtn!.exists()).toBe(true);
    });

    it('populates input with calculation expression when editing', async () => {
        const wrapper = mountCalculator(sampleCalculations);
        const buttons = wrapper.findAll('button[type="button"]');
        const editBtn = buttons.find((b) => b.text() === '✎');
        await editBtn!.trigger('click');
        await wrapper.vm.$nextTick();

        const input = wrapper.find('input[type="text"]');
        expect((input.element as HTMLInputElement).value).toBe('2 + 2');
    });

    it('exits edit mode when "Cancel" is clicked', async () => {
        const wrapper = mountCalculator(sampleCalculations);
        const buttons = wrapper.findAll('button[type="button"]');
        const editBtn = buttons.find((b) => b.text() === '✎');
        await editBtn!.trigger('click');
        await wrapper.vm.$nextTick();

        const cancelBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'Cancel');
        await cancelBtn!.trigger('click');
        await wrapper.vm.$nextTick();

        // Should revert to "=" submit button
        expect(wrapper.text()).not.toContain('Update');
        expect(wrapper.text()).toContain('=');
        // Input should be cleared
        const input = wrapper.find('input[type="text"]');
        expect((input.element as HTMLInputElement).value).toBe('');
    });

    it('calls storeForm.post when submitting a new valid expression', async () => {
        const postMock = vi.fn();
        const putMock = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            expression: '',
            result: 0,
            post: postMock,
            put: putMock,
        } as unknown as InertiaForm<{ expression: string; result: number }>);

        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('3 + 3');
        await wrapper.vm.$nextTick();

        const submitBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === '=');
        await submitBtn!.trigger('click');
        await wrapper.vm.$nextTick();

        expect(store).toHaveBeenCalled();
        expect(postMock).toHaveBeenCalledWith(
            '/calculations',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('calls updateForm.put when submitting in edit mode', async () => {
        const postMock = vi.fn();
        const putMock = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            expression: '',
            result: 0,
            post: postMock,
            put: putMock,
        } as unknown as InertiaForm<{ expression: string; result: number }>);

        const wrapper = mountCalculator(sampleCalculations);

        // Enter edit mode
        const editBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === '✎');
        await editBtn!.trigger('click');
        await wrapper.vm.$nextTick();

        // Input already has '2 + 2', wait for live result
        await wrapper.vm.$nextTick();

        const updateBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'Update');
        await updateBtn!.trigger('click');
        await wrapper.vm.$nextTick();

        expect(update).toHaveBeenCalledWith({ calculation: 1 });
        expect(putMock).toHaveBeenCalledWith(
            '/calculations/1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('does not submit when expression is empty', async () => {
        const postMock = vi.fn();
        const putMock = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            expression: '',
            result: 0,
            post: postMock,
            put: putMock,
        } as unknown as InertiaForm<{ expression: string; result: number }>);

        const wrapper = mountCalculator();
        // Do not set any expression - just try clicking submit (which is disabled)
        // Programmatically call submitCalculation via enter keydown to bypass disabled state
        const input = wrapper.find('input[type="text"]');
        await input.trigger('keydown.enter');
        await wrapper.vm.$nextTick();

        expect(postMock).not.toHaveBeenCalled();
        expect(putMock).not.toHaveBeenCalled();
    });

    it('does not submit when expression is invalid', async () => {
        const postMock = vi.fn();
        const putMock = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            expression: '',
            result: 0,
            post: postMock,
            put: putMock,
        } as unknown as InertiaForm<{ expression: string; result: number }>);

        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('((bad');
        await wrapper.vm.$nextTick();
        await input.trigger('keydown.enter');
        await wrapper.vm.$nextTick();

        expect(postMock).not.toHaveBeenCalled();
        expect(putMock).not.toHaveBeenCalled();
    });

    it('shows live result for pi expression', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('pi * 2');
        await wrapper.vm.$nextTick();
        // pi * 2 ≈ 6.28...
        expect(wrapper.text()).toContain('= 6.28');
    });

    it('clears live result and error when expression becomes empty', async () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('bad!!');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Invalid expression');

        await input.setValue('');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).not.toContain('Invalid expression');
        // Live result preview (= X) should not be visible; the static "=" in submit button is OK
        expect(wrapper.find('p.text-indigo-400').exists()).toBe(false);
    });

    it('has a text input with placeholder "Enter expression…"', () => {
        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        expect((input.element as HTMLInputElement).placeholder).toBe(
            'Enter expression…',
        );
    });

    it('shows the CalcTek heading and History subheading', () => {
        const wrapper = mountCalculator();
        expect(wrapper.text()).toContain('CalcTek');
        expect(wrapper.text()).toContain('History');
    });

    it('shows the result alongside each calculation in history', () => {
        const wrapper = mountCalculator(sampleCalculations);
        expect(wrapper.text()).toContain('4');
        expect(wrapper.text()).toContain('5');
    });

    it('submits via Enter keydown on a valid expression', async () => {
        const postMock = vi.fn();
        const putMock = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            expression: '',
            result: 0,
            post: postMock,
            put: putMock,
        } as unknown as InertiaForm<{ expression: string; result: number }>);

        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('7 + 1');
        await wrapper.vm.$nextTick();
        await input.trigger('keydown.enter');
        await wrapper.vm.$nextTick();

        expect(postMock).toHaveBeenCalled();
    });

    it('clears state after store onSuccess callback fires', async () => {
        let capturedOnSuccess: (() => void) | undefined;
        const postMock = vi.fn(
            (_url: string, opts: { onSuccess?: () => void }) => {
                capturedOnSuccess = opts.onSuccess;
            },
        );
        const putMock = vi.fn();

        vi.mocked(useForm).mockReturnValue({
            expression: '',
            result: 0,
            post: postMock,
            put: putMock,
        } as unknown as InertiaForm<{ expression: string; result: number }>);

        const wrapper = mountCalculator();
        const input = wrapper.find('input[type="text"]');
        await input.setValue('5 + 5');
        await wrapper.vm.$nextTick();

        const submitBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === '=');
        await submitBtn!.trigger('click');
        await wrapper.vm.$nextTick();

        expect(capturedOnSuccess).toBeDefined();
        capturedOnSuccess!();
        await wrapper.vm.$nextTick();

        expect((input.element as HTMLInputElement).value).toBe('');
        expect(wrapper.find('p.text-indigo-400').exists()).toBe(false);
    });

    it('clears state after update onSuccess callback fires', async () => {
        let capturedOnSuccess: (() => void) | undefined;
        const postMock = vi.fn();
        const putMock = vi.fn(
            (_url: string, opts: { onSuccess?: () => void }) => {
                capturedOnSuccess = opts.onSuccess;
            },
        );

        vi.mocked(useForm).mockReturnValue({
            expression: '',
            result: 0,
            post: postMock,
            put: putMock,
        } as unknown as InertiaForm<{ expression: string; result: number }>);

        const wrapper = mountCalculator(sampleCalculations);

        // Start editing
        const editBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === '✎');
        await editBtn!.trigger('click');
        await wrapper.vm.$nextTick();

        // expression is now '2 + 2', live result should be 4
        await wrapper.vm.$nextTick();

        const updateBtn = wrapper
            .findAll('button[type="button"]')
            .find((b) => b.text() === 'Update');
        await updateBtn!.trigger('click');
        await wrapper.vm.$nextTick();

        expect(capturedOnSuccess).toBeDefined();
        capturedOnSuccess!();
        await wrapper.vm.$nextTick();

        // After onSuccess: editingId cleared, expression cleared, no longer in edit mode
        const input = wrapper.find('input[type="text"]');
        expect((input.element as HTMLInputElement).value).toBe('');
        expect(wrapper.text()).not.toContain('Update');
    });
});
