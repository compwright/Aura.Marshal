<?php
namespace Aura\Marshal;

class MockType extends Type\GenericType
{
    public function addFakeRelation($name)
    {
        $this->relations[$name] = true;
    }
}
