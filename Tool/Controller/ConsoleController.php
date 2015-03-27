<?php

namespace Gzfextra\Module\Controller;

use Zend\Config\Factory as ConfigFactory;
use Zend\Db\Metadata\Metadata;
use Zend\Db\RowGateway\RowGateway;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;

/**
 * Class ConsoleController
 *
 * @author  moln.xie@gmail.com
 */
class ConsoleController extends AbstractActionController
{

    private $module, $name, $table, $schema;

    /**
     * @var \Zend\Db\Adapter\Adapter
     */
    private $db;

    public function indexAction()
    {
        return new ViewModel();
    }

    public function mkdirs($path, $mode = 0777)
    {
        $paths  = explode(
            DIRECTORY_SEPARATOR,
            str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, trim($path, '/\/'))
        );
        $root   = '.';
        $result = false;
        foreach ($paths as $dir) {
            if ($dir == '.' || $dir == '..') {
                continue;
            }

            $root .= '/' . $dir;
            if (!is_dir($root)) {
                echo "mkdir: $root\n";
                mkdir($root);
            }
        }
        return $result;
    }

    private $tableMeta;

    private function getTableMeta()
    {
        if (!$this->tableMeta) {
            $db = $this->db;

            $meta = new Metadata($db);
            try {
                $meta->getTable($this->table, $this->schema);
            } catch (\Exception $e) {
                exit($e->getMessage());
            }
            $this->tableMeta = $meta;
        }

        return $this->tableMeta;
    }

    public function createModel()
    {
//        foreach ($meta->getColumns($this->table) as $column) {
//            var_dump($column->getName());
//        }

        $this->createFile('model', ['columns' => $this->getTableMeta()->getColumns($this->table, $this->schema)]);
    }

    private function createFile($model, $params = [])
    {
        $renderer = new PhpRenderer();
        $stack    = new Resolver\TemplatePathStack(array(
            'script_paths' => $this->getServiceLocator()->get('config')['gzfextra_console_create']
        ));
        $renderer->setResolver($stack);

        $view = new ViewModel(
            [
                'module' => $this->module,
                'name'   => $this->name,
                'table'  => $this->table,
                'entity' => $this->params('re') ? '\\' . RowGateway::class :
                        ($this->params('e') ? $this->name : '\ArrayObject'),
            ] + $params
        );

        $view->setTemplate("gzfextra/console/create-$model");
        $content = $renderer->render($view);

        $path = "module/{$this->module}/src/{$this->module}/Model/{$this->name}" .
            ($model == 'table' ? "Table" : "") . ".php";

        if (file_exists($path)) {
            copy($path, $path . '.' . date('mdHis'));
        }

        $this->mkdirs(dirname($path));
        is_dir(dirname($path)) && file_put_contents($path, $content);
        echo "Create file: $path\n";
    }

    public function createTable()
    {
        $this->createFile('table', ['e' => $this->params('e')]);
    }

    public function createAction()
    {
        $this->module = ucfirst($this->params('moduleName'));
        $this->table  = $this->params('tableName');
        $this->name   = ucfirst($this->params('name', str_replace(' ', '', ucwords(str_replace('_', ' ', $this->table)))));
        $this->schema = $this->params('schema');
        $this->db     = $this->getServiceLocator()->get($this->params('db', 'db'));

        $this->getTableMeta();

        if ($this->params('t')) {
            echo "Create table: \n";
            $this->createTable();
        }

        if ($this->params('e')) {
            echo "Create model: \n";
            $this->createModel();
        }

        echo 'Generate config:';
        $this->generateConfig();

        return "\nCreate success!";
    }

    private function generateConfig()
    {
        $path = "module/{$this->module}/config/table.config.php";

        if (file_exists($path)) {
            copy($path, $path . '.' . date('mdHis'));
            $config = include $path;
        } else {
            $config = ['tables' => []];
        }

        $itemConfig = [
            'table' => $this->table,
        ];

        if ($this->params('db')) {
            $itemConfig['adapter'] = $this->params('db');
        }

        if ($this->params('schema')) {
            $itemConfig['schema'] = $this->params('schema');
        }

        if ($this->params('t')) {
            $itemConfig['invokable'] = "$this->module\\Model\\{$this->name}Table";
        }

        if ($this->params('e')) {
            $itemConfig['row'] = "$this->module\\Model\\{$this->name}";
        }

        if ($this->params('re')) {
            $itemConfig['row'] = true;
        }

        $primaries = [];
        foreach ($this->getTableMeta()->getConstraints($this->table, $this->schema) as $constraint) {
            /** @var $constraint \Zend\Db\Metadata\Object\ConstraintObject */
            if ($constraint->getType() == 'PRIMARY KEY') {
                $primaries[] = current($constraint->getColumns());
            }
        }
        if (count($primaries) == 1) {
            $primaries = current($primaries);
        }
        $itemConfig['primary'] = $primaries;

        $config['tables'][$this->name . 'Table'] = $itemConfig;

        ConfigFactory::toFile($path, $config);
        echo 'Generate config success!';
    }
}