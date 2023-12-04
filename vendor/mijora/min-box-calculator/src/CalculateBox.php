<?php

namespace Mijora\MinBoxCalculator;

use Mijora\MinBoxCalculator\Elements\Box;

class CalculateBox
{
    public $items = array();
    public $box;
    public $wall_thickness = 0;
    public $box_max_size = array(false, false, false); //TODO: Make a max box size restriction
    public $debug = false;
    private $items_lines = array(); //TODO: In order to make more efficient use of space, items should be placed in lines when it adding in height. After filling one row as much as possible, only then create another.
    private $debug_actions = array();

    public function __construct($items)
    {
        $this->items = $items;
        $this->box = $this->updateBox(0, 0, 0);
    }

    public function setBoxWallThickness($wall_thickness)
    {
        $this->wall_thickness = $wall_thickness;
        $this->box = $this->updateBox($this->box->getWidth(), $this->box->getHeight(), $this->box->getLength());

        return $this;
    }

    public function setMaxBoxSize($width, $height, $length)
    {
        $this->box->setMaxSize($width, $height, $length);
        $this->box_max_size = array($width, $height, $length);

        return $this;
    }

    public function findMinBoxSize()
    {
        $this->items = $this->sortItems($this->items);

        foreach ( $this->items as $item_id => $item ) {
            $this->addToDebug('Adding item #' . $item_id . ': ' . $this->debugObject($item, true));
            $item_longest_edge = $this->getLongestEdge($item);
            $this->addToDebug('Item longest edge: ' . $item_longest_edge);
            $rotated_item = $this->rotateByEdge($item, $item_longest_edge);
            $this->addToDebug("Rotated item: " . $this->debugObject($rotated_item));

            if ( $this->box->isEmpty() ) {
                $this->box = $this->updateBox($rotated_item->getWidth(), $rotated_item->getHeight(), $rotated_item->getLength());
                $this->addToDebug("Box empty. Box after first item: " . $this->debugObject($this->box));
                $this->addToDebug('***************** END OF ITEM ADD *****************');
                continue;
            }
            $this->box = $this->addItemToBox($this->box, $rotated_item);
            $this->addToDebug("Item added to box. Box: " . $this->debugObject($this->box));
            $this->addToDebug('***************** END OF ITEM ADD *****************');
        }

        return $this->box;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    public function getDebugData()
    {
        return array(
            'items' => $this->items,
            'box' => $this->box,
            'actions' => $this->debug_actions,
        );
    }

    private function updateBox($width, $height, $length)
    {
        $box = new Box($width, $height, $length, $this->wall_thickness);
        $box->setMaxSize($this->box_max_size[0], $this->box_max_size[1], $this->box_max_size[2]);

        return $box;
    }

    private function sortItems($items)
    {
        usort($items, function ($item1, $item2) {
            return $item2->getVolume() <=> $item1->getVolume();
        });

        return $items;
    }

    private function addItemToBox($box, $item)
    {
        $box_sortest_edge = $this->getSortestEdge($box);
        $this->addToDebug('Adding item to box edge: ' . $box_sortest_edge);

        $new_box_width = $box->getWidth();
        $new_box_height = $box->getHeight();
        $new_box_length = $box->getLength();
        if ( $box_sortest_edge == 'width' ) {
            $new_box_width += $item->getWidth();
            if ( $item->getHeight() > $new_box_height ) {
                $new_box_height = $item->getHeight();
            }
            if ( $item->getLength() > $new_box_length ) {
                $new_box_length = $item->getLength();
            }
        } else if ( $box_sortest_edge == 'height' ) {
            $new_box_height += $item->getHeight();
            if ( $item->getWidth() > $new_box_width ) {
                $new_box_width = $item->getWidth();
            }
            if ( $item->getLength() > $new_box_length ) {
                $new_box_length = $item->getLength();
            }
        } else if ( $box_sortest_edge == 'length' ) {
            $new_box_length += $item->getLength();
            if ( $item->getWidth() > $new_box_width ) {
                $new_box_width = $item->getWidth();
            }
            if ( $item->getHeight() > $new_box_height ) {
                $new_box_height = $item->getHeight();
            }
        }

        return $this->updateBox($new_box_width, $new_box_height, $new_box_length);
    }

    private function getSortestEdge($object)
    {
        $min_value = min($object->getWidth(), $object->getHeight(), $object->getLength());

        return array_search($min_value, array(
            'width' => $object->getWidth(),
            'height' => $object->getHeight(),
            'length' => $object->getLength()
        ));
    }

    private function getLongestEdge($object)
    {
        $max_value = max($object->getWidth(), $object->getHeight(), $object->getLength());

        return array_search($max_value, array(
            'width' => $object->getWidth(),
            'height' => $object->getHeight(),
            'length' => $object->getLength()
        ));
    }

    private function rotateByEdge($object, $edge)
    {
        $new_width = 0;
        $new_height = 0;
        $new_length = 0;
        switch ($edge) {
            case 'height':
                $new_width = $object->getHeight();
                $new_height = $object->getWidth();
                $new_length = $object->getLength();
                break;
            case 'length':
                $new_width = $object->getLength();
                $new_height = $object->getHeight();
                $new_length = $object->getWidth();
                break;
            default:
                $new_width = $object->getWidth();
                $new_height = $object->getHeight();
                $new_length = $object->getLength();
        }

        $object->setWidth($new_width);
        $object->setHeight($new_height);
        $object->setLength($new_length);

        if ($new_height < $new_length) {
            $object->setHeight($new_length);
            $object->setLength($new_height);
        }

        return $object;
    }

    private function addToDebug($text)
    {
        if ( $this->debug ) {
            $this->debug_actions[] = $text;
        }
    }

    private function debugObject($object)
    {
        return PHP_EOL . print_r($object, true);
    }
}
