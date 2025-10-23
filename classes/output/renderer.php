namespace mod_smartspe\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {
    public function render_mainpage() {
        $data = new main(); // main.php renderable
        return $this->render_from_template('mod_smartspe/view', $data->export_for_template($this));
    }
}
