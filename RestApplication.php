<?php

namespace Saft\Skeleton;

use Saft\Data\ParserFactory;
use Saft\Data\SerializerFactory;
use Saft\Rdf\NodeFactory;
use Saft\Rdf\StatementFactory;
use Saft\Store\Store;
use Saft\Sparql\Query\QueryFactory;

use Slim\Slim;

/**
 * This class provides a REST interface for the Saft.store
 *
 * The interface is inspered by the SPARQL HTTP interface and should be backward compatible
 * {@url http://www.w3.org/TR/2013/REC-sparql11-http-rdf-update-20130321/}
 *
 * It is documented at {@url http://safting.github.io/doc/restinterface/triplestore/}
 * ?query=:query&… : Store->query($query, $options[…]);
 * ?action=:action
 * ?s=:s&p=:p&o=:o&graph=:g : Store->getMatchingStatements($s, $p, $o, $g, $options[…]);
 */
class RestApplication
{
    private $store;
    private $nf;
    private $sf;
    private $pf;

    public function __construct(Store $store, NodeFactory $nf, StatementFactory $sf, ParserFactory $pf, SerializerFactory $sf)
    {
        $this->store = $store;
        $this->nf = $nf;
        $this->sf = $sf;
        $this->pf = $pf;
    }

    public function run()
    {
        $app = new Slim();
        $app->map('/', function () use ($app) {
            $arguments = [
                'graph' => $app->request()->params('graph'),
                'query' => $app->request()->params('query'),
                'action' => $app->request()->params('action'),
                'subject' => $app->request()->params('s'),
                'predicate' => $app->request()->params('p'),
                'object' => $app->request()->params('o'),
            ];

            // set default action
            if ($arguments['action'] == null) {
                $arguments['action'] = 'get';
                if ($arguments['query'] != null) {
                    $arguments['action'] = 'query';
                }
            }

            $callAction = strtolower($arguments['action']). 'Action';
            $action = new \ReflectionMethod($this, $callAction);
            $methodParameters = $action->getParameters();
            $callParameters = array();
            foreach ($methodParameters as $parameter) {
                $name = $parameter->getName();
                if ($arguments[$name] === null && $parameter->isDefaultValueAvailable()) {
                    $callParameters[] = $parameter->getDefaultValue();
                } else {
                    $callParameters[] = $arguments[$name];
                }
            }

            // ob_start();
            //try {
                $return = call_user_func_array([$this, $callAction], $callParameters);
            /*
            } catch (\Exception $e) {
                $ob = ob_get_flush();
                if (isset($return)) {
                    $payload = $return;
                }
                $return = ['exception' => $e->getMessage(), 'output_buffer' => $ob];
                if (isset($payload)) {
                    $return['payload'] = $payload;
                }
            }
             */
            // ob_clean();

            echo $this->resultEncode($return);
        })->via('GET', 'POST', 'PUT');
        $app->run();
    }

    public function resultEncode($result)
    {
        if ($result instanceof Saft\Store\Result\StatementSetResult || $result instanceof Saft\Rdf\StatementIterator) {
            $serializer = $this->sf->createSerializerFactoryFor('ntriples');
            return $serializer->serializeToString($result, 'ntriples');
        }
        return json_encode($result);
    }

    public function getAction($subject, $predicate, $object, $graph = null)
    {
        $s = $this->nf->createAnyPattern();
        $p = $this->nf->createAnyPattern();
        $o = $this->nf->createAnyPattern();
        $graphNode = null;

        if ($subject != null) {
            $s = $this->nf->createNamedNode($subject);
        }
        if ($predicate != null) {
            $p = $this->nf->createNamedNode($predicate);
        }
        if ($object != null) {
            $o = $this->nf->createNamedNode($object);
        }
        if ($graph != null) {
            $graphNode = $this->nf->createNamedNode($graph);
        }

        $pattern = $this->sf->createStatement($s, $p, $o);
        return $this->store->getMatchingStatements($pattern, $graphNode);
    }

    public function hasAction($subject, $predicate, $object, $graph = null)
    {
        $any = $this->nf->createAnyPattern();
        $s = $this->nf->createNamedNode($subject);
        $p = $this->nf->createNamedNode($predicate);
        $o = $this->nf->createNamedNode($object);
        $graphNode = $this->nf->createNamedNode($graph);

        $pattern = $this->sf->createStatement($s, $p, $o);
        return $this->store-hassMatchingStatements($pattern, $graphNode);
    }

    public function addAction($payload, $mimetype, $graph = null)
    {
        $mimeToSerialization = [
            'text/turtle' => 'turtle',
            'application/rdf+xml' => 'rdfxml',
        ];
        $serialization = $mimeToSerialization[$mimetype];
        $parser = $this->pf->createParserFor($serialization);
        $statements = $parser->parse($payload, $serialization);
        $this->store->addStatements($statements);
    }

    public function deleteAction($subject, $predicate, $object, $graph = null)
    {
        $any = $this->nf->createAnyPattern();
        $s = $this->nf->createNamedNode($subject);
        $p = $this->nf->createNamedNode($predicate);
        $o = $this->nf->createNamedNode($object);
        $graphNode = $this->nf->createNamedNode($graph);

        $pattern = $this->sf->createStatement($s, $p, $o);
        $this->store->deleteMatchingStatements($pattern, $graphNode);
    }

    public function queryAction($query, $graph = null)
    {
        echo $query;
        return;
        $graphNode = $this->nf->createNamedNode($graph);
        return $this->store->query($query, $graphNode);
    }

    public function getGraphsAction()
    {
        $graphs = $this->store->getGraphs();
        $list = array();
        foreach ($graphs as $graph) {
            $list[] = $graph->getUri();
        }
        return $list;
    }

    public function createGraphAction($graph)
    {
        $graphNode = $this->nf->createNamedNode($graph);
        $this->store->createGraph($graphNode);
    }

    public function dropGraphAction($graph)
    {
        $graphNode = $this->nf->createNamedNode($graph);
        $this->store->dropGraph($graphNode);
    }
}
