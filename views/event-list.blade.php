{{-- Keep the nested renderer's view factory so upstream extends/includes resolve there. --}}
{!! \EventManagerIntegration\Helper\RenderBlade::blade('event-list', array_diff_key(get_defined_vars(), ['__env' => true]), EVENTMANAGERINTEGRATION_PATH . 'source/php/Module/Event/views/') !!}
