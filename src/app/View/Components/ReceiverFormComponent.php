<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use App\Models\User;

class ReceiverFormComponent extends Component
{
    public array $users;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $users = User::where('id', '!=', auth()->id())->get();

        foreach($users as $user)
        {
            $this->users[] = [
                'account_id' => $user->account->id,
                'name' => $user->name
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.receiver-form-component');
    }
}
