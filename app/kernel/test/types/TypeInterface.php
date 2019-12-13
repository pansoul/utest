<?php

namespace UTest\Kernel\Test\Types;

interface TypeInterface {
    public function validateComplect($v, $r);
    public function saveComplect();
    public function edit();
    public function create();
    public function delete();
}