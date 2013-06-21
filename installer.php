<?php

/**
 * Base installer for new projects
 *
 * Prepares:
 * - Index.php
 * - Folders (Classes, templates, styles, js)
 * - Classes site, db, template, ajax-redir
 * - Templates header, footer, content
 * - Reset stylesheet
 * - Mootools, Site.js
 *
 */

class BaseInstaller {

    private $userdata;

    public function __construct(){
        if(isset($_POST['data'])){
            $this->userdata = $_POST['data'];
        }

        if(isset($_POST['install']) && $_POST['install'] == 'true'){
            die($this->install($this->userdata));
        } elseif(isset($_GET['install']) && $_GET['location']){
            die($this->install($this->userdata));
        }
    }

    public function install($data){
        $result = '';
        $result .= $this->makeFiles($data['location']);

        $result .= $this->replaceIncDefinitions($data['location'], $data['dbhost'], $data['dbuser'], $data['dbpass'], $data['dbname']);

        return $result;
    }

    public function makeFiles($location){
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/'.$location)){
            mkdir($_SERVER['DOCUMENT_ROOT'].'/'.$location);
        }

        $html = 'Creating index.php...<br>';
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/'.$location.'/'.'index.php', $this->getIndex());

        foreach($this->getFiles() as $folder => $file){
            $html .= 'Creating directory: '.$folder.'...<br>';
            if(!is_dir($_SERVER['DOCUMENT_ROOT'].'/'.$location.'/'.$folder)){
                mkdir($_SERVER['DOCUMENT_ROOT'].'/'.$location.'/'.$folder);
            }

            $html .= '<ul>';
            foreach($file as $filename => $content){
                $html .= '<li>Creating file: '.$folder.'/'.$filename.'...</li>';
                file_put_contents($_SERVER['DOCUMENT_ROOT'].'/'.$location.'/'.$folder.'/'.$filename, $content);
            }
            $html .= '</ul>';
        }

        $html = '<a href="../../../'.$location.'/">Go!</a>';

        return $html;
    }

    public function getFiles($mootools = false){
        $files = array(
            'classes' => array(
                'Ajax.class.php' => $this->getClassesAjax(),
                'Db.class.php' => $this->getClassesDb(),
                'Site.class.php' => $this->getClassesSite(),
                'Template.class.php' => $this->getClassesTemplate()
            ),
            'css' => array(
                'reset.css' => $this->getCssReset(),
                'styles.css' => $this->getCssStyles()
            ),
            'inc' => array(
                'definitions.inc.php' => $this->getIncDefinitions()
            ),
            'js' => array(
                'Site.js' => $this->getJsSite(),
                'mootools-core-1.4.5-full-nocompat-yc.js' => file_get_contents('mootools-core-1.4.5-full-nocompat-yc.js')
            ),
            'templates' => array(
                'content-default.php' => $this->getTemplatesContentDefault(),
                'footer.php' => $this->getTemplatesFooter(),
                'header.php' => $this->getTemplatesHeader()
            )
        );

        return $files;
    }

    public function getIndex(){
        $content = '<?php

require_once(\'classes/Site.class.php\');

$oSite = new Site();

echo $oSite->getPage();';

        return $content;
    }

    public function getClassesAjax(){
        $content = '<?php

class Ajax extends Site {

    public $db;
    public $template;

    public function __construct(){
        $this->db = new Db();
        $this->template = new Template();
    }

    public function getRequest($request, $data){
        switch($request){
            case \'testAjax\':
                $response = $this->testAjax($data);
                break;
            case \'testDb\':
                $response = $this->testDb($data);
                break;
            case \'finalReplacements\':
                $response = $this->finalReplacements();
                break;

            default:
                $response = $this->getDefault($data);
                break;
        }

        die($response);
    }

    public function testAjax($data){
        if($data == \'true\'){
            return \'Ajax call successful\';
        }
    }

    public function testDb($data){
        if($data == \'true\'){
            if(!is_array($this->db->query(\'SHOW TABLES\'))){
                return \'DB connection failed\';
            } else {
                return \'DB connection successful\';
            }
        }
    }

    public function getDefault($data = null){
        return $this->template->getTemplate(\'content-default\', $data);
    }

    public function finalReplacements(){
        if($this->replaceClassesAjax() && $this->replaceJsSite() && $this->replaceTemplatesContentDefault()){
            return true;
        }
    }

    public function replaceClassesAjax(){
        $content = \'<?php

class Ajax extends Site {

    public $db;
    public $template;

    public function __construct(){
        $this->db = new Db();
        $this->template = new Template();
    }

    public function getRequest($request, $data = null){

        if(is_callable(array($this, $request))){
            if(!is_null($data)){
                $response = call_user_func(\\\'Ajax::\\\'.$request, $data);
            } else {
                $response = call_user_func(\\\'Ajax::\\\'.$request);
            }
        } else {
            switch($request){

                default:
                    $response = $this->getDefault($data);
                    break;
            }
        }

        die($response);
    }

    public function getDefault($data = null){
        return $this->template->getTemplate(\\\'content-default\\\', $data);
    }
}\';

        return file_put_contents(\'./classes/Ajax.class.php\', $content);
    }

    public function replaceJsSite(){
        $content = \'var Site = new Class({

    Implements: Events,

    initialize: function(){
        this.attach();
    },

    attach: function(){
        var self = this;


    },

    ajaxRequest: function(request, data, success){
        var ajaxRequest = new Request({
            url: document.location.href,
            method: \\\'post\\\',
            data: {
                ajax: true,
                request: request,
                data: JSON.encode(data)
            },
            onSuccess: function(data){
                if(success){
                    success(data);
                } else {
                    return data;
                }
            },
            onFailure: function(data){
                console.log(data);
            }
        }).send();
    }
});

window.addEvent(\\\'domready\\\', function(){
    var site = new Site();
});\';

        return file_put_contents(\'./js/Site.js\', $content);
    }

    public function replaceTemplatesContentDefault(){
        $content = \'\';

        return file_put_contents(\'./templates/content-default.php\', $content);
    }
}';

        return $content;
    }

    public function getClassesDb(){
        $content = '<?php

class Db extends Site{
    public $db;

    private $db_user;
    private $db_pass;
    private $db_database;

    public $conn;

    public function __construct(){

        $this->db_user = DB_USER;
        $this->db_pass = DB_PASS;
        $this->db_database = DB_DATABASE;
        $this->db_host = DB_HOST;

        $this->conn = mysqli_connect($this->db_host,$this->db_user,$this->db_pass,$this->db_database);

    }

    public function query($query = null){

        if(is_null($query)){
            die(\'No query given!\');
        } else {
            if(!$result = $this->conn->query($query)){
                return \'Error processing query <strong>"\'.$query.\'"</strong>\';
            } elseif($result->num_rows == 0){
                return \'Returned empty set for query <strong>"\'.$query.\'"</strong>\';
            } else {
                while($line = $result->fetch_assoc()){
                    $lines[] = $line;
                }

                return $lines;
            }
        }
    }

    public function insert($table, $data = array()){
        $query = \'INSERT INTO \'.$table.\' SET \';
        foreach($data as $column => $value){
            if(!is_null($value)) $query .= $column.\' = "\'.mysqli_real_escape_string($this->conn, $value).\'", \';
        }
        if(!$result = $this->conn->query(substr($query, 0, -2))){
            return \'Error inserting row with query <strong>"\'.$query.\'"</strong><br>\';
        } else {
            return true;
        }
    }

    public function update($table, $id, $data = array()){
        $query = \'UPDATE \'.$table.\' SET \';
        foreach($data as $column => $value){
            if(!is_null($value)) $query .= $column.\' = "\'.mysqli_real_escape_string($this->conn, $value).\'", \';
        }
        $query = substr($query, 0, -2);
        $query .= \' WHERE id = \'.$id;
        if(!$result = $this->conn->query($query)){
            return \'Error updating row with query <strong>"\'.$query.\'"</strong><br>\';
        } else {
            return true;
        }
    }

    public function delete($table, $id){
        $query = \'DELETE FROM \'.$table.\' WHERE id = \'.$id;
        if(!$result = $this->conn->query($query)){
            return \'Error deleting row with query <strong>"\'.$query.\'"</strong><br>\';
        } else {
            return true;
        }
    }
}';

        return $content;
    }

    public function getClassesSite(){
        $content = '<?php

class Site {

    public $db;
    public $template;
    public $ajax;

    public function autoloader($class){
        require_once(\'classes/\'.$class.\'.class.php\');
    }

    public function __construct(){
        if(!isset($_SESSION)) session_start();
        require_once(\'inc/definitions.inc.php\');

        spl_autoload_register(\'Site::autoloader\');

        $this->db = new Db();
        $this->template = new Template();
        $this->ajax = new Ajax();

        if(isset($_POST[\'ajax\']) && $_POST[\'ajax\'] == true && isset($_POST[\'request\'])){
            die($this->ajax->getRequest($_POST[\'request\'], $_POST[\'data\']));
        }
    }

    public function getPage(){
        $this->template->getTemplate(\'header\');
        $this->template->getTemplate(\'content-default\', null);
        $this->template->getTemplate(\'footer\');
    }

    public function error($errorMsg,$line=\'undefined\',$file=\'undefined\'){
        die(\'Error: \'.$errorMsg.\'<br>Line: \'.$line.\'<br>File: \'.$file);
    }
}';

        return $content;
    }

    public function getClassesTemplate(){
        $content = '<?php

class Template extends Site {

    public function __construct(){

    }

    public function getTemplate($template, $content = null){
        if(!file_exists(\'templates/\'.$template.\'.php\')){
            $this->error(\'Template \'.$template.\'.php not found!\',__LINE__,__FILE__);
        } else {
            include_once(\'templates/\'.$template.\'.php\');
        }
    }
}';

        return $content;
    }

    public function getCssReset(){
        $content = '/*html5doctor.com Reset Stylesheet v1.6.1 Last Updated: 2010-09-17 Author: RichardClark - http://richclarkdesign.com Twitter:@rich_clark*/ html,body,div,span,object,iframe,h1,h2,h3,h4,h5,h6,p,blockquote,pre,abbr,address,cite,code,del,dfn,em,img,ins,kbd,q,samp,small,strong,sub,sup,var,b,i,dl,dt,dd,ol,ul,li,fieldset,form,label,legend,table,caption,tbody,tfoot,thead,tr,th,td,article,aside,canvas,details,figcaption,figure,footer,header,hgroup,menu,nav,section,summary,time,mark,audio,video{margin:0;padding:0;border:0;outline:0;font-size:100%;vertical-align:baseline;background:transparent}body{line-height:1;font-size:62.5%}article,aside,details,figcaption,figure,footer,header,hgroup,menu,nav,section{display:block}navul{list-style:none}blockquote,q{quotes:none}blockquote:before,blockquote:after,q:before,q:after{content:\'\';content:none}a{margin:0;padding:0;font-size:100%;vertical-align:baseline;background:transparent}/*changecolourstosuityourneeds*/ins{background-color:#ff9;color:#000;text-decoration:none}mark{background-color:#ff9;color:#000;font-style:italic;font-weight:bold}del{text-decoration:line-through}abbr[title],dfn[title]{border-bottom:1px dotted;cursor:help}table{border-collapse:collapse;border-spacing:0} /*change border colour to suit your needs*/hr{display:block;height:1px;border:0;border-top:1px solid #cccccc;margin:1em 0;padding:0}input,select{vertical-align:middle}';

        return $content;
    }

    public function getCssStyles(){
        $content = '';

        return $content;
    }

    public function getIncDefinitions(){
        $content = '<?php

define(\'DB_HOST\',       \'\');
define(\'DB_USER\',       \'\');
define(\'DB_PASS\',       \'\');
define(\'DB_DATABASE\',   \'\');';

        return $content;
    }

    public function getJsSite(){
        $content = 'var Site = new Class({

    Implements: Events,

    initialize: function(){
        this.attach();
    },

    attach: function(){
        var self = this;

        $(document.body).getElement(\'#testAjax\').addEvent(\'click\', function(){
            var ajaxTest = self.ajaxRequest(\'testAjax\', true, function(data){
                $(document.body).getElement(\'#ajaxResult\').set(\'html\', data);
            });
        });

        $(document.body).getElement(\'#testDb\').addEvent(\'click\', function(e){
            var dbTest = self.ajaxRequest(\'testDb\', true, function(data){
                $(document.body).getElement(\'#dbResult\').set(\'html\', data);
            });
        });

        $(document.body).getElement(\'#finalReplacements\').addEvent(\'click\', function(e){
            var finalReplacements = self.ajaxRequest(\'finalReplacements\', true, function(data){
                window.location.href = window.location.href;
            });
        });
    },

    ajaxRequest: function(request, data, success){
        var ajaxRequest = new Request({
            url: document.location.href,
            method: \'post\',
            data: {
                ajax: true,
                request: request,
                data: JSON.encode(data)
            },
            onSuccess: function(data){
                if(success){
                    success(data);
                } else {
                    return data;
                }
            },
            onFailure: function(data){
                console.log(data);
            }
        }).send();
    }
});

window.addEvent(\'domready\', function(){
    var site = new Site();
});';

        return $content;
    }

    public function getTemplatesContentDefault(){
        $content = '<h1>Hello world!</h1>

<form id="testingCalls" action="" method="post">
    <fieldset>
        <div id="ajax">
            <input type="button" id="testAjax" value="Test Ajax call">
            <span id="ajaxResult"></span>
        </div>
        <div id="db">
            <input type="button" id="testDb" value="Test DB connection">
            <span id="dbResult"></span>
        </div>
        <div id="replacements">
            <input type="button" id="finalReplacements" value="Final preparations">
        </div>
    </fieldset>
</form>';

        return $content;
    }

    public function getTemplatesFooter(){
        $content = '</body>
</html>';

        return $content;
    }

    public function getTemplatesHeader(){
        $content = '<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <link href="css/reset.css" type="text/css" rel="stylesheet">
    <link href="css/styles.css" type="text/css" rel="stylesheet">

    <script src="js/mootools-core-1.4.5-full-nocompat-yc.js" type="text/javascript"></script>
    <script src="js/Site.js" type="text/javascript"></script>
</head>
<body>';

        return $content;
    }

    public function replaceIncDefinitions($location, $dbhost, $dbuser, $dbpass, $dbname){
        $content = '<?php

define(\'DB_HOST\',       \''.$dbhost.'\');
define(\'DB_USER\',       \''.$dbuser.'\');
define(\'DB_PASS\',       \''.$dbpass.'\');
define(\'DB_DATABASE\',   \''.$dbname.'\');';

        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/'.$location.'/inc/definitions.inc.php', $content);

        return '<br>Modifying definitions.inc.php...<br>';
    }
}

$oBaseInstaller = new BaseInstaller();

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style type="text/css">html,body,div,span,object,iframe,h1,h2,h3,h4,h5,h6,p,blockquote,pre,abbr,address,cite,code,del,dfn,em,img,ins,kbd,q,samp,small,strong,sub,sup,var,b,i,dl,dt,dd,ol,ul,li,fieldset,form,label,legend,table,caption,tbody,tfoot,thead,tr,th,td,article,aside,canvas,details,figcaption,figure,footer,header,hgroup,menu,nav,section,summary,time,mark,audio,video{margin:0;padding:0;border:0;outline:0;font-size:100%;vertical-align:baseline;background:transparent}body{line-height:1;font-size:62.5%}article,aside,details,figcaption,figure,footer,header,hgroup,menu,nav,section{display:block}navul{list-style:none}blockquote,q{quotes:none}blockquote:before,blockquote:after,q:before,q:after{content:'';content:none}a{margin:0;padding:0;font-size:100%;vertical-align:baseline;background:transparent}ins{background-color:#ff9;color:#000;text-decoration:none}mark{background-color:#ff9;color:#000;font-style:italic;font-weight:bold}del{text-decoration:line-through}abbr[title],dfn[title]{border-bottom:1px dotted;cursor:help}table{border-collapse:collapse;border-spacing:0}hr{display:block;height:1px;border:0;border-top:1px solid #cccccc;margin:1em 0;padding:0}input,select{vertical-align:middle}label{float:left;width:200px;}
    body{font-family:'Trebuchet MS';background:rgba(0,0,0,0.1);}#wrapper{width:720px;height:520px;font-size:2em;background:#fff;border-radius:20px;position:absolute;left:50%;top:50%;margin:-300px 0 0 -400px;padding:40px;}h1{margin-bottom:1em}input[type=button],input[type=submit]{width:80px;padding:10px;border:1px solid #fff;box-shadow:0 0 2px #000;border-radius:10px;cursor:pointer;margin:10px 0;background:#rgba(0,0,0,0.1)}
    </style>
    <script type="text/javascript" src="mootools-core-1.4.5-full-nocompat-yc.js"></script>
</head>
<body>
    <div id="wrapper">
    <h1>MP Base Installer</h1>
    <form id="installForm">
        <fieldset id="page1">
            <div class="formrow">
                <label for="location">Location:</label><br>
                <?php echo $_SERVER['DOCUMENT_ROOT']; ?>/ <input type="text" placeholder="" id="location">
            </div>

            <input class="continue" type="button" value="Continue">
        </fieldset>
        <fieldset id="page2">
            <div class="formrow">
                <label for="dbhost">Database Host:</label>
                <input type="text" placeholder="localhost" id="dbhost">
            </div>
            <div class="formrow">
                <label for="dbuser">Database User:</label>
                <input type="text" placeholder="root" id="dbuser">
            </div>
            <div class="formrow">
                <label for="dbpass">Database Password:</label>
                <input type="text" placeholder="" id="dbpass">
            </div>
            <div class="formrow">
                <label for="dbname">Database name:</label>
                <input type="text" placeholder="" id="dbname">
            </div>

            <input class="continue" type="button" value="Continue">
        </fieldset>
        <fieldset id="overview">
            <div class="formrow">
                <span class="overviewLabel">Location:</span>
                <span id="val-location"></span>
            </div>
            <div class="formrow">
                <span class="overviewLabel">Database Host:</span>
                <span id="val-dbhost"></span>
            </div>
            <div class="formrow">
                <span class="overviewLabel">Database User:</span>
                <span id="val-dbuser"></span>
            </div>
            <div class="formrow">
                <span class="overviewLabel">Database Password:</span>
                <span id="val-dbpass"></span>
            </div>
            <div class="formrow">
                <span class="overviewLabel">Database Name:</span>
                <span id="val-dbname"></span>
            </div>

            <input type="submit" value="Install">
        </fieldset>
    </form>
    <div id="result"></div>
</div>
<script type="text/javascript">
window.addEvent('domready', function(){
    var page1,
        page2,
        overview,
        result,
        el,
        req,
        self = this;

    page1 = $(document.body).getElement('#page1');
    page2 = $(document.body).getElement('#page2');
    overview = $(document.body).getElement('#overview');
    result = $(document.body).getElement('#result');

    [page2,overview,result].each(function(el){
        el.setStyle('display','none')
    });

    $(document.body).getElement('#installForm').addEvent('submit', function(event){
        event.preventDefault();
    });

    page1.getElement('input.continue').addEvent('click', function(){
        this.getParent().setStyle('display','none');
        page2.setStyle('display','block');
    });

    page2.getElement('input.continue').addEvent('click', function(){

        self.installForm = {
            'location': page1.getElement('#location').get('value'),
            'dbhost': page2.getElement('#dbhost').get('value'),
            'dbuser': page2.getElement('#dbuser').get('value'),
            'dbpass': page2.getElement('#dbpass').get('value'),
            'dbname': page2.getElement('#dbname').get('value')
        };

        Object.each(self.installForm, function(value, key){
            el = overview.getElement('#val-'+key).set('html', value);
        });

        this.getParent().setStyle('display','none');
        overview.setStyle('display','block');
    });

    overview.getElement('input[type=submit]').addEvent('click', function(event){
        event.preventDefault();

        req = new Request({
            url: document.location.href,
            method: 'post',
            data: {
                'install': true,
                'data': self.installForm
            },
            onSuccess: function(data){
                overview.setStyle('display','none');
                result.set('html', data).setStyle('display','block');
            }
        }).send();
    });
});
</script>
</body>
</html>