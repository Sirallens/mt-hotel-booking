<?php
class HBS_Loader {
    protected $actions = [];

    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions[] = compact('hook','component','callback','priority','accepted_args');
    }

    public function run() {
        foreach ($this->actions as $hook) {
            add_action($hook['hook'], [$hook['component'], $hook['callback']], $hook['priority'], $hook['accepted_args']);
        }
    }
}
