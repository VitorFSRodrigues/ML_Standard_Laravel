<?php
 
namespace App\Helpers\PowerGridThemes;
 
use PowerComponents\LivewirePowerGrid\Themes\Tailwind;
 
class TailwindHeaderFixed extends Tailwind
{
    public string $name = 'tailwind';
 
    public function table(): array
    {
        return [
            'layout' => [
                'base' => 'p-3 align-middle inline-block w-full sm:px-6 lg:px-8',

                // ✅ este costuma ser o wrapper direto da tabela
                'div' => 'w-full rounded-t-lg',


                // ✅ deixe container sem overflow-y (evita scroll duplo)
                // 'container' => 'w-full',
                'container' => 'relative w-full',

                // mantém responsivo e permite scroll-x quando precisar
                'table' => 'min-w-full w-full table-auto',

                'actions' => 'flex gap-2',
            ],

            'header' => [
                'thead' => 'bg-[#f4f6f9] dark:bg-pg-primary-900',
                'tr'    => 'bg-[#f4f6f9] dark:bg-pg-primary-900',

                // ✅ sticky no TH (não no thead)
                'th' => 'sticky -top-px z-30 bg-[#f4f6f9] dark:bg-pg-primary-900 font-extrabold px-3 py-3 text-left text-xs tracking-wider whitespace-nowrap',

                'thAction' => '!font-bold',
            ],

            'body' => [
                'tbody' => '',
                'tr' => '',
                'td' => 'px-3 py-2 align-top',

                // a linha inteira dos filtros fica sticky
                'trFilters' => 'sticky top-[42px] z-40 bg-white dark:bg-pg-primary-800',

                // garante fundo por célula também (evita “transparência”)
                'tdFilters' => 'bg-[#f4f6f9] dark:bg-pg-primary-800',

                'tdActionsContainer' => 'flex gap-2',
            ],

        ];
    }

    public function footer(): array
    {
        return [
            'view' => $this->root() . '.footer',
            'select' => 'appearance-none !bg-none focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 rounded-md border-0 bg-transparent py-0.5 px-4 pr-7 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-auto',
            'footer' => 'dark:bg-pg-primary-700 dark:border-pg-primary-600',
            'footer_with_pagination' => 'md:flex md:flex-row w-full items-center py-3 bg-white overflow-y-auto pl-2 pr-2 relative dark:bg-pg-primary-900',
        ];
    }
 
    public function cols(): array
    {
        return [
            'div' => 'select-none flex items-center gap-1',
        ];
    }
 
    public function editable(): array
    {
        return [
            'view' => $this->root() . '.editable',
            'input' => 'focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 w-full rounded-md border-0 bg-transparent py-0.5 px-2 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-full',
        ];
    }
 
    public function toggleable(): array
    {
        return [
            'view' => $this->root() . '.toggleable',
        ];
    }
 
    public function checkbox(): array
    {
        return [
            'th' => 'px-6 py-2 text-left text-xs font-medium text-pg-primary-500 tracking-wider',
            'base' => '',
            'input' => 'form-checkbox dark:border-dark-600 border-1 dark:bg-dark-800 rounded border-gray-300 transition duration-100 ease-in-out h-4 w-4 text-primary-500 focus:ring-primary-500 dark:ring-offset-dark-900',
        ];
    }
 
    public function radio(): array
    {
        return [
            'th' => 'px-6 py-0 text-left text-xs font-medium text-pg-primary-500 tracking-wider',
            'base' => '',
            'label' => 'flex items-center space-x-3',
            'input' => 'form-radio rounded-full transition ease-in-out duration-100',
        ];
    }
 
    public function filterBoolean(): array
    {
        return [
            'view' => $this->root() . '.filters.boolean',
            'base' => 'min-w-[5rem]',
            'select' => 'appearance-none !bg-none focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 w-full rounded-md border-0 bg-transparent py-0.5 px-2 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-full',
        ];
    }
 
    public function filterDatePicker(): array
    {
        return [
            'base' => '',
            'view' => $this->root() . '.filters.date-picker',
            'input' => 'flatpickr flatpickr-input focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 w-full rounded-md border-0 bg-transparent py-0.5 px-2 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-auto',
        ];
    }
 
    public function filterMultiSelect(): array
    {
        return [
            'view' => $this->root() . '.filters.multi-select',
            'base' => 'inline-block relative w-full',
            'select' => 'mt-1',
        ];
    }
 
    public function filterNumber(): array
    {
        return [
            'view' => $this->root() . '.filters.number',
            'input' => 'w-full min-w-[5rem] block focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 rounded-md border-0 bg-transparent py-0.5 pl-2 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6',
        ];
    }
 
    public function filterSelect(): array
    {
        return [
            'view' => $this->root() . '.filters.select',
            'base' => '',
            'select' => 'appearance-none !bg-none focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 rounded-md border-0 bg-transparent py-0.5 px-2 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-full',
        ];
    }
 
    public function filterInputText(): array
    {
        return [
            'view' => $this->root() . '.filters.input-text',
            'base' => 'min-w-[9.5rem]',
            'select' => 'appearance-none !bg-none focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 w-full rounded-md border-0 bg-transparent py-0.5 px-2 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-full',
            'input' => 'focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 w-full rounded-md border-0 bg-transparent py-0.5 px-2 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-full',
        ];
    }
 
    public function searchBox(): array
    {
        return [
            'input' => 'focus:ring-primary-600 focus-within:focus:ring-primary-600 focus-within:ring-primary-600 dark:focus-within:ring-primary-600 flex items-center rounded-md ring-1 transition focus-within:ring-2 dark:ring-pg-primary-600 dark:text-pg-primary-300 text-gray-600 ring-gray-300 dark:bg-pg-primary-800 bg-white dark:placeholder-pg-primary-400 w-full rounded-md border-0 bg-transparent py-0.5 px-2 ring-0 placeholder:text-gray-400 focus:outline-none sm:text-sm sm:leading-6 w-full pl-8',
            'iconClose' => 'text-pg-primary-400 dark:text-pg-primary-200',
            'iconSearch' => 'text-pg-primary-300 mr-2 w-5 h-5 dark:text-pg-primary-200',
        ];
    }
}