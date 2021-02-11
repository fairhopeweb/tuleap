<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\XML;

use SimpleXMLElement;
use Tracker;
use Tuleap\Tracker\FormElement\XML\XMLFormElement;
use Tuleap\Tracker\TrackerColor;

final class XMLTracker
{
    /**
     * @var string
     * @readonly
     */
    private $id;
    /**
     * @var string
     * @readonly
     */
    private $name = '';
    /**
     * @var string
     * @readonly
     */
    private $item_name;
    /**
     * @var string
     * @readonly
     */
    private $description = '';
    /**
     * @var TrackerColor
     * @readonly
     */
    private $color;
    /**
     * @var string
     * @readonly
     */
    private $parent_id = '0';
    /**
     * @var string
     */
    private $submit_instructions = '';
    /**
     * @var string
     */
    private $browse_instructions = '';
    /**
     * @var XMLFormElement[]
     * @readonly
     */
    private $form_elements = [];

    /**
     * @param string|IDGenerator $id
     */
    public function __construct($id, string $item_name)
    {
        if ($id instanceof IDGenerator) {
            $this->id = sprintf('%s%d', Tracker::XML_ID_PREFIX, $id->getNextId());
        } else {
            $this->id = $id;
        }
        $this->item_name = $item_name;
        $this->color     = TrackerColor::default();
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withName(string $name): self
    {
        $new       = clone $this;
        $new->name = $name;
        return $new;
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withDescription(string $description): self
    {
        $new              = clone $this;
        $new->description = $description;
        return $new;
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withColor(TrackerColor $color): self
    {
        $new        = clone $this;
        $new->color = $color;
        return $new;
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withParentId(string $parent_id): self
    {
        $new            = clone $this;
        $new->parent_id = $parent_id;
        return $new;
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withSubmitInstructions(string $submit_instructions): self
    {
        $new                      = clone $this;
        $new->submit_instructions = $submit_instructions;
        return $new;
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withBrowseInstructions(string $browse_instructions): self
    {
        $new                      = clone $this;
        $new->browse_instructions = $browse_instructions;
        return $new;
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withFormElement(XMLFormElement ...$form_elements): self
    {
        $new                = clone $this;
        $new->form_elements = array_merge($this->form_elements, $form_elements);
        return $new;
    }

    public static function fromTracker(Tracker $tracker): self
    {
        return (new self($tracker->getXMLId(), $tracker->getItemName()))
            ->withName($tracker->getName())
            ->withDescription($tracker->getDescription())
            ->withColor($tracker->getColor())
            ->withSubmitInstructions($tracker->submit_instructions ?? '')
            ->withBrowseInstructions($tracker->browse_instructions ?? '');
    }

    public static function fromTrackerInProjectContext(Tracker $tracker): self
    {
        $parent = $tracker->getParent();
        return self::fromTracker($tracker)
            ->withParentId($parent !== null ? $parent->getXMLId() : "0");
    }

    public function export(SimpleXMLElement $trackers_xml): SimpleXMLElement
    {
        $tracker_xml = $trackers_xml->addChild('tracker');
        return $this->exportTracker($tracker_xml);
    }

    public function exportTracker(SimpleXMLElement $tracker_xml): SimpleXMLElement
    {
        $tracker_xml->addAttribute('id', $this->id);
        $tracker_xml->addAttribute('parent_id', $this->parent_id);

        $cdata_section_factory = new \XML_SimpleXMLCDATAFactory();
        $cdata_section_factory->insert($tracker_xml, 'name', $this->name);
        $cdata_section_factory->insert($tracker_xml, 'item_name', $this->item_name);
        $cdata_section_factory->insert($tracker_xml, 'description', $this->description);
        $cdata_section_factory->insert($tracker_xml, 'color', $this->color->getName());

        if ($this->submit_instructions !== '') {
            $cdata_section_factory->insert($tracker_xml, 'submit_instructions', $this->submit_instructions);
        }
        if ($this->browse_instructions !== '') {
            $cdata_section_factory->insert($tracker_xml, 'browse_instructions', $this->browse_instructions);
        }

        $tracker_xml->addChild('cannedResponses');
        $tracker_xml->addChild('formElements');
        if (count($this->form_elements) > 0) {
            foreach ($this->form_elements as $form_element) {
                $form_element->export($tracker_xml->formElements);
            }
        }

        return $tracker_xml;
    }
}