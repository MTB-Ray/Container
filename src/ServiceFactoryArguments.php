<?php 
namespace ClanCats\Container;

class ServiceFactoryArguments 
{
    /**
     * Static instance constructor from array for eye candy
     * 
     *     ServiceFactoryArguments::from([
     *         '@session.storage.redis',
     *         ':session_token',
     *         600, // session lifetime
     *     ])
     */
    public static function from(array $arguments) : ServiceFactoryArguments
    {
        return new static($arguments);
    }

    /**
     * Available service factory argument types
     * Subclasses would be cleaner, but this could have 
     * a real performance impact so lets do it oldschool 
     */ 
    const RAW = 0;
    const PARAMETER = 1;
    const DEPENDENCY = 2;

    /**
     * An array of arguments
     * 
     * @var array[[string, int]]
     */
    protected $arguments = [];

    /**
     * Construct new arguments object with array
     * 
     * @param array             $arguments
     * @return void
     */
    public function __construct(array $arguments = [])
    {
        $this->addArgumentsFromArray($arguments);
    }

    /**
     * Add a service argument of type
     * 
     * @param mixed             $argumentValue
     * @param int               $argumentType
     * @return self
     */
    private function addArgument($argumentValue, int $argumentType) : ServiceFactoryArguments
    {
        $this->arguments[] = [$argumentValue, $argumentType]; return $this;
    }

    /**
     * Add a simply raw argument,
     * 
     * @param mixed             $argumentValue
     * @return self
     */
    public function addRaw($argumentValue) : ServiceFactoryArguments
    {
        return $this->addArgument($argumentValue, static::RAW);
    }

    /**
     * Add a simply raw argument,
     * 
     * @param mixed             $argumentValue
     * @return self
     */
    public function addDependency($argumentValue) : ServiceFactoryArguments
    {
        return $this->addArgument($argumentValue, static::DEPENDENCY);
    }

    /**
     * Add a simply raw argument,
     * 
     * @param mixed             $argumentValue
     * @return self
     */
    public function addParameter($argumentValue) : ServiceFactoryArguments
    {
        return $this->addArgument($argumentValue, static::PARAMETER);
    }

    /**
     * Add arguments with a simple array
     * 
     *  - @ prefix indicates dependency
     *  - : prefix indicates parameter
     * 
     * @param array                 $argumentsArray
     * @return void
     */
    public function addArgumentsFromArray(array $argumentsArray) : void
    {
        foreach($argumentsArray as $argument)
        {
            if (is_string($argument) && ($argument[0] === '@' || $argument[0] === ':'))
            {
                if ($argument[0] === '@') {
                    $this->addDependency(substr($argument, 1));
                } elseif ($argument[0] === ':') {
                    $this->addParameter(substr($argument, 1));
                }
            } else  {
                $this->addRaw($argument);
            }
        }
    }

    /**
     * Resolve the current arguments from the given container instance and
     * return them as array
     * 
     * @param Container             $container
     * @return array
     */
    public function resolve(Container $container) : array
    {
        $resolvedArguments = [];
        foreach($this->arguments as list($argumentValue, $argumentType))
        {
            switch ($argumentType) 
            {
                case static::RAW:
                    $resolvedArguments[] = $argumentValue;
                break;
                case static::DEPENDENCY:
                    $resolvedArguments[] = $container->get($argumentValue);
                break;
                case static::PARAMETER:
                    $resolvedArguments[] = $container->getParameter($argumentValue);
                break;
            }
        }

        return $resolvedArguments;
    }

    /**
     * Return all arguments
     * 
     * @return array[[string => int]]
     */
    public function getAll() : array
    {
        return $this->arguments;
    }
}   