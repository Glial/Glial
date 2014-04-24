use \Glial\Synapse\Controller;
use \Glial\Cli\Glial;
use \Glial\Cli\Color;
use \Glial\Cli\Shell;
use \Glial\Acl\Acl;

class Install extends Controller
{

    function composer()
    {
        $this->view = false;
        echo PHP_EOL . Glial::header() . PHP_EOL;

        echo "To finish install run : '" . Color::getColoredString("php glial install", "purple") . "'" . PHP_EOL;
    }
    
}
