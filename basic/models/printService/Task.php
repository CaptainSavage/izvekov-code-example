<?php

namespace app\models\printService;

class Task
{
    private $handlerClassModel = '';
    private $property = [];
    private $alias = '';
    private $description = '';

    public function setAlias(string $alias)
    {
        $this->alias = $alias;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setHandlerClassModel(string $handlerClassModel)
    {
        $this->handlerClassModel = $handlerClassModel;
    }

    public function getHandlerClassModel(): string
    {
        return $this->handlerClassModel;
    }

    public function setProperty(array $data)
    {
        $this->property = $data;
    }

    public function getProperty(): array
    {
        return $this->property;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}