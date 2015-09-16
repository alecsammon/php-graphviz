<?php
/**
 * PHP GraphViz wrapper
 *
 * PHP version 4 and 5
 *
 * Copyright (c) 2001-2007, Dr. Volker Göbbels <vmg@arachnion.de> and
 * Sebastian Bergmann <sb@sebastian-bergmann.de>. All rights reserved.
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category  Image
 * @package   Image_GraphViz
 * @author    Dr. Volker Göbbels <vmg@arachnion.de>
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @author    Karsten Dambekalns <k.dambekalns@fishfarm.de>
 * @author    Michael Lively Jr. <mlively@ft11.net>
 * @author    Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @author    Alec Sammon <alec.sammon@googlemail.com>
 * @copyright 2001-2007 Dr. Volker Göbbels <vmg@arachnion.de> and Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version   CVS: $Id$
 * @link      http://github.com/alecsammon/graphviz
 * @link      http://www.graphviz.org/
 * @since     File available since Release 0.1.0
 */

namespace Graphviz;

/**
 * Interface to AT&T's GraphViz tools.
 *
 * The GraphViz class allows for the creation of and to work with directed
 * and undirected graphs and their visualization with AT&T's GraphViz tools.
 *
 * <code>
 * <?php
 * require_once 'Image/GraphViz.php';
 *
 * $graph = new Image_GraphViz();
 *
 * $graph->addNode(
 *   'Node1',
 *   array(
 *     'URL'   => 'http://link1',
 *     'label' => 'This is a label',
 *     'shape' => 'box'
 *   )
 * );
 *
 * $graph->addNode(
 *   'Node2',
 *   array(
 *     'URL'      => 'http://link2',
 *     'fontsize' => '14'
 *   )
 * );
 *
 * $graph->addNode(
 *   'Node3',
 *   array(
 *     'URL'      => 'http://link3',
 *     'fontsize' => '20'
 *   )
 * );
 *
 * $graph->addEdge(
 *   array(
 *     'Node1' => 'Node2'
 *   ),
 *   array(
 *     'label' => 'Edge Label'
 *   )
 * );
 *
 * $graph->addEdge(
 *   array(
 *     'Node1' => 'Node2'
 *   ),
 *   array(
 *     'color' => 'red'
 *   )
 * );
 *
 * $graph->image();
 * ?>
 * </code>
 */
class Graph
{
    /**
     * Base path to GraphViz commands
     *
     * @var string
     */
    var $binPath = '';

    /**
     * Path to GraphViz/dot command
     *
     * @var string
     */
    var $dotCommand = 'dot';

    /**
     * Path to GraphViz/neato command
     *
     * @var string
     */
    var $neatoCommand = 'neato';

    /**
     * Representation of the graph
     *
     * @var array
     */
    var $graph = [
        'edgesFrom' => [],
        'nodes' => [],
        'attributes' => [],
        'directed' => true,
        'clusters' => [],
        'subgraphs' => [],
        'name' => 'G',
        'strict' => true,
    ];

    /**
     * Whether to return PEAR_Error instance on failures instead of FALSE
     *
     * @var boolean
     * @access protected
     */
    var $returnFalseOnError = true;

    /**
     * Constructor.
     *
     * Setting the name of the Graph is useful for including multiple image
     * maps on one page. If not set, the graph will be named 'G'.
     *
     * @param boolean $directed Directed (TRUE) or undirected (FALSE) graph.
     *                             Note: You MUST pass a boolean, and not just
     *                             an  expression that evaluates to TRUE or
     *                             FALSE (i.e. NULL, empty string, 0 will NOT
     *                             work)
     * @param array $attributes Attributes of the graph
     * @param string $name Name of the Graph
     * @param boolean $strict Whether to collapse multiple edges between
     *                             same nodes
     * @param boolean $returnError Set to TRUE to return PEAR_Error instances
     *                             on failures instead of FALSE
     *
     * @access public
     */
    public function __construct(
        $directed = true,
        $attributes = [],
        $name = 'G',
        $strict = true,
        $returnError = false
    )
    {
        $this->setDirected($directed);
        $this->setAttributes($attributes);
        $this->graph['name'] = $name;
        $this->graph['strict'] = (boolean)$strict;

        $this->returnFalseOnError = !$returnError;
    }

    /**
     * Sets directed/undirected flag for the graph.
     *
     * Note: You MUST pass a boolean, and not just an expression that evaluates
     *       to TRUE or FALSE (i.e. NULL, empty string, 0 will not work)
     *
     * @param boolean $directed Directed (TRUE) or undirected (FALSE) graph.
     *
     * @return void
     * @access public
     */
    public function setDirected($directed)
    {
        if (is_bool($directed)) {
            $this->graph['directed'] = $directed;
        }
    }

    /**
     * Sets attributes of the graph.
     *
     * @param array $attributes Attributes to be set for the graph.
     *
     * @return void
     * @access public
     */
    public function setAttributes($attributes)
    {
        if (is_array($attributes)) {
            $this->graph['attributes'] = $attributes;
        }
    }

    /**
     * Outputs image of the graph in a given format
     *
     * This methods send HTTP headers
     *
     * @param string $format Format of the output image. This may be one
     *                        of the formats supported by GraphViz.
     * @param string $command "dot" or "neato"
     *
     * @return boolean TRUE on success, FALSE or PEAR_Error otherwise
     * @access public
     */
    public function image($format = 'svg', $command = null)
    {
        $file = $this->saveParsedGraph();
        $outputfile = $file . '.' . $format;

        $rendered = $this->renderDotFile($file, $outputfile, $format,
            $command);
        if ($rendered !== true) {
            return $rendered;
        }

        $sendContentLengthHeader = true;

        switch (strtolower($format)) {
            case 'gif':
            case 'png':
            case 'bmp':
            case 'jpeg':
            case 'tiff':
                header('Content-Type: image/' . $format);
                break;

            case 'tif':
                header('Content-Type: image/tiff');
                break;

            case 'jpg':
                header('Content-Type: image/jpeg');
                break;

            case 'ico':
                header('Content-Type: image/x-icon');
                break;

            case 'wbmp':
                header('Content-Type: image/vnd.wap.wbmp');
                break;

            case 'pdf':
                header('Content-Type: application/pdf');
                break;

            case 'mif':
                header('Content-Type: application/vnd.mif');
                break;

            case 'vrml':
                header('Content-Type: application/x-vrml');
                break;

            case 'svg':
                header('Content-Type: image/svg+xml');
                break;

            case 'plain':
            case 'plain-ext':
                header('Content-Type: text/plain');
                break;

            default:
                header('Content-Type: application/octet-stream');
                $sendContentLengthHeader = false;
        }

        if ($sendContentLengthHeader) {
            header('Content-Length: ' . filesize($outputfile));
        }

        $return = true;
        if (readfile($outputfile) === false) {
            $return = false;
        }
        @unlink($outputfile);

        return $return;
    }

    /**
     * Saves GraphViz markup to file (in DOT language)
     *
     * @param string $file File to write the GraphViz markup to.
     *
     * @return string File to which the GraphViz markup was written, FALSE or
     *                or PEAR_Error on failure.
     * @access public
     */
    public function saveParsedGraph($file = '')
    {
        $parsedGraph = $this->parse();

        if (!empty($parsedGraph)) {
            if (empty($file)) {
                $file = tempnam(sys_get_temp_dir(), 'Tux');;
            }

            file_put_contents($file, $parsedGraph);

            return $file;
        }

        throw new \Exception('Could not save graph');
    }

    /**
     * Parses the graph into GraphViz markup.
     *
     * @return string GraphViz markup
     * @access public
     */
    public function parse()
    {
        $parsedGraph = (empty($this->graph['strict'])) ? '' : 'strict ';
        $parsedGraph .= (empty($this->graph['directed'])) ? 'graph ' : 'digraph ';
        $parsedGraph .= $this->escape($this->graph['name']) . " {\n";

        $indent = '    ';

        $attr = $this->escapeArray($this->graph['attributes']);

        foreach ($attr as $key => $value) {
            $parsedGraph .= $indent . $key . '=' . $value . ";\n";
        }

        $groups = $this->getGroups();
        foreach ($this->graph['nodes'] as $group => $nodes) {
            if (!in_array($group, $groups)) {
                $parsedGraph .= $this->nodes($nodes, $indent);
            }
        }
        $tops = $this->getTopGraphs();
        foreach ($tops as $group) {
            $parsedGraph .= $this->subgraph($group, $indent);
        }

        if (!empty($this->graph['directed'])) {
            $separator = ' -> ';
        } else {
            $separator = ' -- ';
        }

        foreach ($this->graph['edgesFrom'] as $from => $toNodes) {
            $from = $this->escape($from);

            foreach ($toNodes as $to => $edges) {
                $to = $this->escape($to);

                foreach ($edges as $info) {
                    $f = $from;
                    $t = $to;

                    if (array_key_exists('portFrom', $info)) {
                        $f .= ':' . $this->escape($info['portFrom']);
                    }

                    if (array_key_exists('portTo', $info)) {
                        $t .= ':' . $this->escape($info['portTo']);
                    }

                    $parsedGraph .= $indent . $f . $separator . $t;

                    if (!empty($info['attributes'])) {
                        $attributeList = [];

                        foreach ($this->escapeArray($info['attributes']) as $key => $value) {
                            switch ($key) {
                                case 'lhead':
                                case 'ltail':
                                    if (strncasecmp($value, 'cluster', 7)) {
                                        $value = 'cluster_' . $value;
                                    }
                                    break;
                            }
                            $attributeList[] = $key . '=' . $value;
                        }

                        $parsedGraph .= ' [ ' . implode(',', $attributeList) . ' ]';
                    }

                    $parsedGraph .= ";\n";
                }
            }
        }

        return $parsedGraph . "}\n";
    }

    // --

    /**
     * Returns a safe "ID" in DOT syntax
     *
     * @param string $input string to use as "ID"
     * @param boolean $html whether to attempt detecting HTML-like content
     *
     * @return string
     * @access protected
     */
    private function escape($input, $html = false)
    {
        switch (strtolower($input)) {
            case 'node':
            case 'edge':
            case 'graph':
            case 'digraph':
            case 'subgraph':
            case 'strict':
                return '"' . $input . '"';
        }

        if (is_bool($input)) {
            return ($input) ? 'true' : 'false';
        }

        if ($html && (strpos($input, '</') !== false
                || strpos($input, '/>') !== false)
        ) {
            return '<' . $input . '>';
        }

        if (preg_match('/^([a-z_][a-z_0-9]*|-?(\.[0-9]+|[0-9]+(\.[0-9]*)?))$/i',
            $input)) {
            return $input;
        }

        return '"' . str_replace(["\r\n", "\n", "\r", '"'],
            ['\n', '\n', '\n', '\"'], $input) . '"';
    }

    /**
     * Escapes an (attribute) array
     *
     * Detects if an attribute is <html>, contains double-quotes, etc...
     *
     * @param array $input input to escape
     *
     * @return array input escaped
     * @access protected
     */
    private function escapeArray($input)
    {
        $output = [];

        foreach ((array)$input as $k => $v) {
            switch ($k) {
                case 'label':
                case 'headlabel':
                case 'taillabel':
                    $v = $this->escape($v, true);
                    break;
                default:
                    $v = $this->escape($v);
                    $k = $this->escape($k);
            }

            $output[$k] = $v;
        }

        return $output;
    }

    /**
     * Returns a list of cluster/subgraph IDs
     *
     * @return array
     * @access protected
     */
    private function getGroups()
    {
        $groups = array_merge(array_keys($this->graph['clusters']),
            array_keys($this->graph['subgraphs']));

        return array_unique($groups);
    }

    /**
     * Output nodes
     *
     * @param array $nodes nodes list
     * @param string $indent space indentation
     *
     * @return string output
     * @access protected
     */
    private function nodes($nodes, $indent)
    {
        if (!is_array($nodes)) {
            return '';
        }

        $parsedGraph = '';
        foreach ($nodes as $node => $attributes) {
            $parsedGraph .= $indent . $this->escape($node);

            $attributeList = [];

            foreach ($this->escapeArray($attributes) as $key => $value) {
                $attributeList[] = $key . '=' . $value;
            }

            if (!empty($attributeList)) {
                $parsedGraph .= ' [ ' . implode(',', $attributeList) . ' ]';
            }

            $parsedGraph .= ";\n";
        }

        return $parsedGraph;
    }

    /**
     * Returns a list of top groups
     *
     * @return array
     * @access protected
     */
    private function getTopGraphs()
    {
        $top = [];
        $groups = $this->getGroups();

        foreach ($groups as $id) {
            $isTop = ($id === 'default');
            if (isset($this->graph['clusters'][$id])
                && $this->graph['clusters'][$id]['embedIn'] === 'default'
            ) {
                $isTop = true;
            }
            if (isset($this->graph['subgraphs'][$id])
                && $this->graph['subgraphs'][$id]['embedIn'] === 'default'
            ) {
                $isTop = true;
            }
            if ($isTop) {
                $top[] = $id;
            }
        }

        return array_unique($top);
    }

    /**
     * Generates output for a group
     *
     * @return string output
     * @access protected
     */
    private function subgraph($group, &$indent)
    {
        $parsedGraph = '';
        $nodes = null;
        if (isset($this->graph['nodes'][$group])) {
            $nodes = $this->graph['nodes'][$group];
        }

        if ($group !== 'default') {
            $type = null;
            $_group = $this->escape($group);

            if (isset($this->graph['clusters'][$group])) {
                $type = 'clusters';
                if (strncasecmp($group, 'cluster', 7)) {
                    $_group = $this->escape('cluster_' . $group);
                }
            } elseif (isset($this->graph['subgraphs'][$group])) {
                $type = 'subgraphs';
            }
            $parsedGraph .= $indent . 'subgraph ' . $_group . " {\n";

            $indent .= '    ';

            if ($type !== null && isset($this->graph[$type][$group])) {
                $cluster = $this->graph[$type][$group];

                $attr = [];
                foreach ($this->escapeArray($cluster['attributes']) as $key => $value) {
                    $attr[] = $key . '=' . $value;
                }

                if (strlen($cluster['title'])) {
                    $attr[] = 'label='
                        . $this->escape($cluster['title'], true);
                }

                if ($attr) {
                    $parsedGraph .= $indent . 'graph [ ' . implode(',', $attr)
                        . " ];\n";
                }
            }
        }

        if ($nodes) {
            $parsedGraph .= $this->nodes($nodes, $indent);
        }

        foreach ($this->getSubgraphs($group) as $_group) {
            $parsedGraph .= $this->subgraph($_group, $indent);
        }

        if ($group !== 'default') {
            $indent = substr($indent, 0, -4);

            $parsedGraph .= $indent . "}\n";
        }

        return $parsedGraph;
    }

    /**
     * Returns a list of sub-groups for a given parent group
     *
     * @param string $parent Group ID
     *
     * @return array list of group IDs
     * @access protected
     */
    private function getSubgraphs($parent)
    {
        $subgraphs = [];
        foreach ($this->graph['clusters'] as $id => $info) {
            if ($info['embedIn'] === $parent) {
                $subgraphs[] = $id;
            }
        }
        foreach ($this->graph['subgraphs'] as $id => $info) {
            if ($info['embedIn'] === $parent) {
                $subgraphs[] = $id;
            }
        }

        return $subgraphs;
    }

    /**
     * Renders a given dot file into a given format.
     *
     * @param string $dotfile The absolute path of the dot file to use.
     * @param string $outputfile The absolute path of the file to save to.
     * @param string $format Format of the output image. This may be one
     *                           of the formats supported by GraphViz.
     * @param string $command "dot" or "neato"
     *
     * @return boolean TRUE if the file was saved, FALSE or PEAR_Error
     *                 otherwise.
     * @access public
     */
    public function renderDotFile(
        $dotfile,
        $outputfile,
        $format = 'svg',
        $command = null
    )
    {
        if (!file_exists($dotfile)) {
            throw new \Exception('Could not find dot file');
        }

        $oldmtime = file_exists($outputfile) ? filemtime($outputfile) : 0;

        switch ($command) {
            case 'dot':
            case 'neato':
                break;
            default:
                $command = $this->graph['directed'] ? 'dot' : 'neato';
        }
        $command_orig = $command;

        $command = $this->binPath . (($command == 'dot') ? $this->dotCommand
                : $this->neatoCommand);

        $command .= ' -T' . escapeshellarg($format)
            . ' -o' . escapeshellarg($outputfile)
            . ' ' . escapeshellarg($dotfile)
            . ' 2>&1';
        exec($command, $msg, $return_val);

        clearstatcache();
        if (file_exists($outputfile) && filemtime($outputfile) > $oldmtime
            && $return_val == 0
        ) {
            return true;
        } elseif ($this->returnFalseOnError) {
            return false;
        }

        throw new \Exception($command_orig . ' command failed: '
            . implode("\n", $msg));
    }

    /**
     * Returns image (data) of the graph in a given format.
     *
     * @param string $format Format of the output image. This may be one
     *                        of the formats supported by GraphViz.
     * @param string $command "dot" or "neato"
     *
     * @return string The image (data) created by GraphViz, FALSE or PEAR_Error
     *                on error
     * @access public
     * @since  Method available since Release 1.1.0
     */
    public function fetch($format = 'svg', $command = null)
    {
        $file = $this->saveParsedGraph();
        $outputfile = $file . '.' . $format;

        $rendered = $this->renderDotFile($file, $outputfile, $format,
            $command);
        if ($rendered !== true) {
            return $rendered;
        }

        @unlink($file);

        $fp = fopen($outputfile, 'rb');

        if (!$fp) {
            if ($this->returnFalseOnError) {
                return false;
            }
            throw new \Exception('Could not read rendered file');
        }

        $data = fread($fp, filesize($outputfile));
        fclose($fp);
        @unlink($outputfile);

        return $data;
    }

    /**
     * Adds a cluster to the graph.
     *
     * A cluster is a subgraph with a rectangle around it.
     *
     * @param string $id ID.
     * @param string $title Title.
     * @param array $attributes Attributes of the cluster.
     * @param string $group ID of group to nest cluster into
     *
     * @return void
     * @access public
     * @see    addSubgraph()
     */
    public function addCluster($id, $title, $attributes = [], $group = 'default')
    {
        $this->graph['clusters'][$id]['title'] = $title;
        $this->graph['clusters'][$id]['attributes'] = $attributes;
        $this->graph['clusters'][$id]['embedIn'] = $group;
        if (!isset($this->graph['nodes'][$id])) {
            $this->graph['nodes'][$id] = [];
        }
    }

    /**
     * Adds a subgraph to the graph.
     *
     * @param string $id ID.
     * @param string $title Title.
     * @param array $attributes Attributes of the cluster.
     * @param string $group ID of group to nest subgraph into
     *
     * @return void
     * @access public
     */
    public function addSubgraph($id, $title, $attributes = [], $group = 'default')
    {
        $this->graph['subgraphs'][$id]['title'] = $title;
        $this->graph['subgraphs'][$id]['attributes'] = $attributes;
        $this->graph['subgraphs'][$id]['embedIn'] = $group;
        if (!isset($this->graph['nodes'][$id])) {
            $this->graph['nodes'][$id] = [];
        }
    }

    /**
     * Adds a note to the graph.
     *
     * @param string $name Name of the node.
     * @param array $attributes Attributes of the node.
     * @param string $group Group of the node.
     *
     * @return void
     * @access public
     */
    public function addNode($name, $attributes = [], $group = 'default')
    {
        $this->graph['nodes'][$group][$name] = $attributes;
    }

    /**
     * Removes a node from the graph.
     *
     * This method doesn't remove edges associated with the node.
     *
     * @param string $name Name of the node to be removed.
     * @param string $group Group of the node.
     *
     * @return void
     * @access public
     */
    public function removeNode($name, $group = 'default')
    {
        if (isset($this->graph['nodes'][$group][$name])) {
            unset($this->graph['nodes'][$group][$name]);
        }
    }

    /**
     * Removes an edge from the graph.
     *
     * @param array $edge Start and End node of the edge to be removed.
     * @param integer $id specific edge ID (only usefull when multiple edges
     *                      exist between the same 2 nodes)
     *
     * @return void
     * @access public
     */
    public function removeEdge($edge, $id = null)
    {
        if (!is_array($edge)) {
            return;
        }

        $from = key($edge);
        $to = $edge[$from];

        if (!is_null($id)) {
            if (isset($this->graph['edgesFrom'][$from][$to][$id])) {
                unset($this->graph['edgesFrom'][$from][$to][$id]);

                if (count($this->graph['edgesFrom'][$from][$to]) == 0) {
                    unset($this->graph['edgesFrom'][$from][$to]);
                }
            }
        } elseif (isset($this->graph['edgesFrom'][$from][$to])) {
            unset($this->graph['edgesFrom'][$from][$to]);
        }
    }

    /**
     * Adds attributes to the graph.
     *
     * @param array $attributes Attributes to be added to the graph.
     *
     * @return void
     * @access public
     */
    public function addAttributes($attributes)
    {
        if (is_array($attributes)) {
            $this->graph['attributes'] = array_merge($this->graph['attributes'], $attributes);
        }
    }

    /**
     * Loads a graph from a file in Image_GraphViz format
     *
     * @param string $file File to load graph from.
     *
     * @return void
     * @access public
     */
    public function load($file)
    {
        if ($serializedGraph = implode('', @file($file))) {
            $g = unserialize($serializedGraph);

            if (!is_array($g)) {
                return;
            }

            // Convert old storage format to new one
            $defaults = [
                'edgesFrom' => [],
                'nodes' => [],
                'attributes' => [],
                'directed' => true,
                'clusters' => [],
                'subgraphs' => [],
                'name' => 'G',
                'strict' => true,
            ];

            $this->graph = array_merge($defaults, $g);

            if (isset($this->graph['edges'])) {
                foreach ($this->graph['edges'] as $id => $nodes) {
                    $attr = (isset($this->graph['edgeAttributes'][$id]))
                        ? $this->graph['edgeAttributes'][$id]
                        : [];

                    $this->addEdge($nodes, $attr);
                }

                unset($this->graph['edges']);
                unset($this->graph['edgeAttributes']);
            }
        }
    }

    /**
     * Adds an edge to the graph.
     *
     * Examples:
     * <code>
     * $g->addEdge(array('node1' => 'node2'));
     * $attr = array(
     *     'label' => '+1',
     *     'style' => 'dashed',
     * );
     * $g->addEdge(array('node3' => 'node4'), $attr);
     *
     * // With port specification
     * $g->addEdge(array('node5' => 'node6'), $attr, array('node6' => 'portA'));
     * $g->addEdge(array('node7' => 'node8'), null, array('node7' => 'portC',
     *                                                    'node8' => 'portD'));
     * </code>
     *
     * @param array $edge Start => End node of the edge.
     * @param array $attributes Attributes of the edge.
     * @param array $ports Start node => port, End node => port
     *
     * @return integer an edge ID that can be used with {@link removeEdge()}
     * @access public
     */
    public function addEdge($edge, $attributes = [], $ports = [])
    {
        if (!is_array($edge)) {
            return;
        }

        $from = key($edge);
        $to = $edge[$from];
        $info = [];

        if (is_array($ports)) {
            if (array_key_exists($from, $ports)) {
                $info['portFrom'] = $ports[$from];
            }

            if (array_key_exists($to, $ports)) {
                $info['portTo'] = $ports[$to];
            }
        }

        if (is_array($attributes)) {
            $info['attributes'] = $attributes;
        }

        if (!empty($this->graph['strict'])) {
            if (!isset($this->graph['edgesFrom'][$from][$to][0])) {
                $this->graph['edgesFrom'][$from][$to][0] = $info;
            } else {
                $this->graph['edgesFrom'][$from][$to][0] = array_merge($this->graph['edgesFrom'][$from][$to][0], $info);
            }
        } else {
            $this->graph['edgesFrom'][$from][$to][] = $info;
        }

        return count($this->graph['edgesFrom'][$from][$to]) - 1;
    }

    /**
     * Save graph to file in Image_GraphViz format
     *
     * This saves the serialized version of the instance, not the
     * rendered graph.
     *
     * @param string $file File to save the graph to.
     *
     * @return string File the graph was saved to, FALSE or PEAR_Error on
     *                failure.
     * @access public
     */
    public function save($file = '')
    {
        $serializedGraph = serialize($this->graph);

        if (empty($file)) {
            $file = tempnam(sys_get_temp_dir(), 'Tux');;
        }

        file_put_contents($file, $serializedGraph);

        return $file;

    }
}
