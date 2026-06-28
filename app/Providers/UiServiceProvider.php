<?php

namespace App\Providers;

use App\View\Components\Ui\Alert;
use App\View\Components\Ui\Badge;
use App\View\Components\Ui\Button;
use App\View\Components\Ui\Card;
use App\View\Components\Ui\Checkbox;
use App\View\Components\Ui\ChoicesOffline;
use App\View\Components\Ui\Drawer;
use App\View\Components\Ui\Dropdown;
use App\View\Components\Ui\File;
use App\View\Components\Ui\Form;
use App\View\Components\Ui\Header;
use App\View\Components\Ui\Hr;
use App\View\Components\Ui\Icon;
use App\View\Components\Ui\Input;
use App\View\Components\Ui\ListItem;
use App\View\Components\Ui\MenuItem;
use App\View\Components\Ui\MenuSeparator;
use App\View\Components\Ui\Modal;
use App\View\Components\Ui\Pagination;
use App\View\Components\Ui\Password;
use App\View\Components\Ui\Popover;
use App\View\Components\Ui\Select;
use App\View\Components\Ui\SelectGroup;
use App\View\Components\Ui\Stat;
use App\View\Components\Ui\Tab;
use App\View\Components\Ui\Table;
use App\View\Components\Ui\Tabs;
use App\View\Components\Ui\Textarea;
use App\View\Components\Ui\Toast;
use App\View\Components\Ui\Toggle;
use Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class UiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerComponents();
        $this->registerBladeDirectives();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(config_path('ui.php'), 'ui');
    }

    protected function registerComponents(): void
    {
        Blade::component('ui-button', Button::class);
        Blade::component('ui-card', Card::class);
        Blade::component('ui-icon', Icon::class);
        Blade::component('ui-input', Input::class);
        Blade::component('ui-list-item', ListItem::class);
        Blade::component('ui-modal', Modal::class);
        Blade::component('ui-menu-item', MenuItem::class);
        Blade::component('ui-header', Header::class);
        Blade::component('ui-pagination', Pagination::class);
        Blade::component('ui-popover', Popover::class);

        $prefix = config('ui.prefix');

        Blade::component($prefix.'alert', Alert::class);
        Blade::component($prefix.'badge', Badge::class);
        Blade::component($prefix.'button', Button::class);
        Blade::component($prefix.'card', Card::class);
        Blade::component($prefix.'checkbox', Checkbox::class);
        Blade::component($prefix.'choices-offline', ChoicesOffline::class);
        Blade::component($prefix.'drawer', Drawer::class);
        Blade::component($prefix.'dropdown', Dropdown::class);
        Blade::component($prefix.'file', File::class);
        Blade::component($prefix.'form', Form::class);
        Blade::component($prefix.'select-group', SelectGroup::class);
        Blade::component($prefix.'header', Header::class);
        Blade::component($prefix.'hr', Hr::class);
        Blade::component($prefix.'icon', Icon::class);
        Blade::component($prefix.'input', Input::class);
        Blade::component($prefix.'list-item', ListItem::class);
        Blade::component($prefix.'modal', Modal::class);
        Blade::component($prefix.'menu-item', MenuItem::class);
        Blade::component($prefix.'menu-separator', MenuSeparator::class);
        Blade::component($prefix.'pagination', Pagination::class);
        Blade::component($prefix.'popover', Popover::class);
        Blade::component($prefix.'password', Password::class);
        Blade::component($prefix.'select', Select::class);
        Blade::component($prefix.'stat', Stat::class);
        Blade::component($prefix.'table', Table::class);
        Blade::component($prefix.'tab', Tab::class);
        Blade::component($prefix.'tabs', Tabs::class);
        Blade::component($prefix.'textarea', Textarea::class);
        Blade::component($prefix.'toast', Toast::class);
        Blade::component($prefix.'toggle', Toggle::class);
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('scope', function ($expression) {
            $directiveArguments = preg_split("/,(?![^\(\(]*[\)\)])/", $expression);
            $directiveArguments = array_map('trim', $directiveArguments);

            [$name, $functionArguments] = $directiveArguments;

            $uses = Arr::except(array_flip($directiveArguments), [$name, $functionArguments]);
            $uses = array_flip($uses);
            array_push($uses, '$__env');
            array_push($uses, '$__bladeCompiler');
            $uses = implode(',', $uses);

            $name = str_replace('.', '___', $name);

            return "<?php \$__bladeCompiler = \$__bladeCompiler ?? null; \$loop = null; \$__env->slot({$name}, function({$functionArguments}) use ({$uses}) { \$loop = (object) \$__env->getLoopStack()[0] ?>";
        });

        Blade::directive('endscope', function () {
            return '<?php }); ?>';
        });
    }
}
