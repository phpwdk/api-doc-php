<?php

namespace phpwdk\apidoc;

use phpwdk\apidoc\lib\ParseComment;

/**
 * ApiDoc生成
 * Class ApiDoc
 *
 * @package phpwdk\apidoc
 */
class ApiDoc
{
    /**
     * @var array - 结构化的数组
     */
    private $ApiTree = [];

    /**
     * @var array - 要生成API的Class类名
     */
    private $class = [];

    /**
     * @var array - 忽略生成的类方法名
     */
    private $filterMethod = ['__construct'];
    private $filterClass  = ['think\admin\controller', 'app\cut_video\service\tableservice'];

    /**
     * ApiDoc 构造函数.
     *
     * @param array $config - 配置信息
     */
    public function __construct(array $config)
    {
        // 需要解析的类
        if (isset($config['class'])) {
            $this->class = array_merge($this->class, $config['class']);
        }
        // 忽略生成的类方法
        if (isset($config['filter_method'])) {
            $this->filterMethod = array_merge($this->filterMethod, $config['filter_method']);
        }
        // 忽略生成的类方法
        if (isset($config['filter_class'])) {
            $this->filterClass = array_merge($this->filterClass, $config['filter_class']);
        }
    }

    /**
     * 获取API文档数据
     *
     * @param int $type - 方法过滤，默认只获取 public类型 方法
     *                  ReflectionMethod::IS_STATIC
     *                  ReflectionMethod::IS_PUBLIC
     *                  ReflectionMethod::IS_PROTECTED
     *                  ReflectionMethod::IS_PRIVATE
     *                  ReflectionMethod::IS_ABSTRACT
     *                  ReflectionMethod::IS_FINAL
     *
     * @return array
     */
    public function getApiDoc(int $type = \ReflectionMethod::IS_PUBLIC): array
    {
        foreach ($this->class as $classItem) {
            $actionInfo = $this->_getActionComment($classItem, $type);
            if (count($actionInfo) >= 1) {
                $this->ApiTree[$classItem]           = $this->_getClassComment($classItem);
                $this->ApiTree[$classItem]['action'] = $actionInfo;
            }
        }
        return $this->ApiTree;
    }

    /**
     * 获取类的注释
     *
     * @param string $class - 类名称(存在命名空间时要完整写入) eg: $class = 'phpwdk\\apidoc\\ApiDoc';
     *
     * @return array - 返回格式为数组（未获取到注释时返回空数组）
     */
    private function _getClassComment(string $class): array
    {
        try {
            $reflection      = new \ReflectionClass($class);
            $classDocComment = $reflection->getDocComment();
        } catch (\Exception $exception) {
            return [];
        }
        $parse = new ParseComment();
        return $parse->parseCommentToArray($classDocComment);
    }

    /**
     * 获取指定类下方法的注释
     *
     * @param     $class - 类名称(存在命名空间时要完整写入) eg: $class = 'phpwdk\\apidoc\\ApiDoc';
     * @param int $type  - 方法过滤，默认只获取 public类型 方法
     *                   ReflectionMethod::IS_STATIC
     *                   ReflectionMethod::IS_PUBLIC
     *                   ReflectionMethod::IS_PROTECTED
     *                   ReflectionMethod::IS_PRIVATE
     *                   ReflectionMethod::IS_ABSTRACT
     *                   ReflectionMethod::IS_FINAL
     *
     * @return array - 返回格式为数组（未获取到注释时返回空数组）
     */
    private function _getActionComment($class, int $type = \ReflectionMethod::IS_PUBLIC): array
    {
        try {
            $reflection = new \ReflectionClass($class);
            //只允许生成public方法
            $method = $reflection->getMethods($type);
        } catch (\Exception $exception) {
            return [];
        }
        $comments = [];
        foreach ($method as $action) {
            try {
                $parse          = new ParseComment();
                $actionComments = $parse->parseCommentToArray($action->getDocComment());
                if (count($actionComments) >= 1 && !in_array($action->name, $this->filterMethod) && !in_array(strtolower($action->class), $this->filterClass)) {
                    $comments[$action->name] = $actionComments;
                }
            } catch (\Exception $exception) {
                continue;
            }
        }
        return $comments;
    }
}
