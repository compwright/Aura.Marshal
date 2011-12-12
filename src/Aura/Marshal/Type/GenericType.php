<?php
/**
 * 
 * This file is part of the Aura project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Marshal\Type;
use Aura\Marshal\Collection\BuilderInterface as CollectionBuilderInterface;
use Aura\Marshal\Data;
use Aura\Marshal\Exception;
use Aura\Marshal\Record\BuilderInterface as RecordBuilderInterface;
use Aura\Marshal\Relation\RelationInterface;

/**
 * 
 * Describes a particular type within the domain, and retains an IdentityMap
 * of records for the type. Converts loaded data to record objects lazily.
 * 
 * @package Aura.Marshal
 * 
 */
class GenericType extends Data
{
    /**
     * 
     * A builder to create collection objects for this type.
     * 
     * @var object
     * 
     */
    protected $collection_builder;
    
    /**
     * 
     * The record field representing its unique identifier value. The
     * IdentityMap will be keyed on these values.
     * 
     * @var string
     * 
     */
    protected $identity_field;
    
    /**
     * 
     * An array of fields to index on for quicker lookups.  The array format
     * is:
     * 
     *     $index_fields[$field_name][$field_value] = (array) $identity_values;
     * 
     * @var array
     * 
     */
    protected $index_fields = array();
    
    /**
     * 
     * A builder to create record objects for this type.
     * 
     * @var object
     * 
     */
    protected $record_builder;
    
    /**
     * 
     * The class expected from the record builder. This is used to determine
     * if elements in the IdentityMap have been converted to record objects.
     * 
     * @var string
     * 
     */
    protected $record_class;
    
    /**
     * 
     * An array of relationship descriptions, where the key is a
     * field name for the record and the value is a relation object.
     * 
     * @var array
     * 
     */
    protected $relation = array();
    
    /**
     * 
     * Sets the name of the field that uniquely identifies each record for
     * this type.
     * 
     * @param string $identity_field The identity field name.
     * 
     * @return void
     * 
     */
    public function setIdentityField($identity_field)
    {
        $this->identity_field = $identity_field;
    }
    
    /**
     * 
     * Returns the name of the field that uniquely identifies each record of
     * this type.
     * 
     * @return string
     * 
     */
    public function getIdentityField()
    {
        return $this->identity_field;
    }
    
    /**
     * 
     * Sets the fields that should be indexed at load() time; removes all
     * previous indexes.
     * 
     * @param array $fields The fields to be indexed.
     * 
     * @return void
     * 
     */
    public function setIndexFields(array $fields = array())
    {
        $this->index_fields = array();
        foreach ($fields as $field) {
            $this->index_fields[$field] = array();
        }
    }
    
    /**
     * 
     * Sets the name of the expected record class; this is used to determine
     * if elements in the IdentityMap have been converted to record objects.
     * 
     * @param string $record_class The identity field name.
     * 
     * @return void
     * 
     */
    public function setRecordClass($record_class)
    {
        $this->record_class = $record_class;
    }
    
    /**
     * 
     * Returns the name of the expected record class.
     * 
     * @return string
     * 
     */
    public function getRecordClass()
    {
        return $this->record_class;
    }
    
    /**
     * 
     * Sets the builder object to create record objects.
     * 
     * @param RecordBuilderInterface $record_builder The builder object.
     * 
     * @return void
     * 
     */
    public function setRecordBuilder(RecordBuilderInterface $record_builder)
    {
        $this->record_builder = $record_builder;
    }
    
    /**
     * 
     * Returns the builder that creates record objects.
     * 
     * @return object
     * 
     */
    public function getRecordBuilder()
    {
        return $this->record_builder;
    }
    
    /**
     * 
     * Sets the builder object to create collection objects.
     * 
     * @param CollectionBuilderInterface $collectionBuilder The builder object.
     * 
     * @return void
     * 
     */
    public function setCollectionBuilder(CollectionBuilderInterface $collection_builder)
    {
        $this->collection_builder = $collection_builder;
    }
    
    /**
     * 
     * Returns the builder that creates collection objects.
     * 
     * @return object
     * 
     */
    public function getCollectionBuilder()
    {
        return $this->collection_builder;
    }
    
    /**
     * 
     * Loads the IdentityMap for this type with data for record objects. 
     * 
     * Typically, the $data value is a sequential array of associative arrays. 
     * As long as the $data value can be iterated over and accessed as an 
     * array, you can pass in any kind of $data.
     * 
     * The elements from $data will be placed into the IdentityMap; the
     * IdentityMap key will be the value of the identity field in the element.
     * 
     * You can call load() multiple times, but records already in the 
     * IdentityMap will not be overwritten.
     * 
     * The loaded elements are cast to objects; this allows consistent
     * addressing of elements before and after conversion to record objects.
     * 
     * The loaded elements will be converted to record objects by the
     * record builder only as you request them from the IdentityMap.
     * 
     * @param array $data Record data to load into the IdentityMap.
     * 
     * @return array The identity values from the data elements, regardless
     * of whether they were loaded or not.
     * 
     */
    public function load($data)
    {
        // what indexes do we need to track?
        $index_fields    = array_keys($this->index_fields);
        
        // return a list of the identity values in $data
        $identity_values = array();
        
        // what is the identity field for the type?
        $identity_field  = $this->getIdentityField();
        
        // load each data element as a record
        foreach ($data as $record) {
            
            // cast the element to an object for consistent addressing
            $record = (object) $record;
            
            // retain the identity value on the record
            $identity_value    = $record->$identity_field;
            $identity_values[] = $identity_value;
            
            // does the identity already exist in the map?
            if (! isset($this->data[$identity_value])) {
                // no, retain it in the map ...
                $this->data[$identity_value] = $record;
                // ... and put the identity value into the indexes
                foreach ($index_fields as $field) {
                    $value = $record->$field;
                    $this->index_fields[$field][$value][] = $identity_value;
                }
            }
        }
        
        // return the list of identity values in $data, and done
        return $identity_values;
    }
    
    /**
     * 
     * Returns the array keys for the for the records in the IdentityMap;
     * the keys were generated at load() time from the identity field values.
     * 
     * @return array
     * 
     */
    public function getIdentityValues()
    {
        return array_keys($this->data);
    }
    
    /**
     * 
     * Returns the values for a particular field for all the records in the
     * IdentityMap.
     * 
     * @param string $field The field name to get values for.
     * 
     * @return array An array of key-value pairs where the key is the identity
     * value and the value is the requested field value.
     * 
     */
    public function getFieldValues($field)
    {
        $values = array();
        foreach ($this->data as $identity_value => $record) {
            $values[$identity_value] = $record->$field;
        }
        return $values;
    }
    
    /**
     * 
     * Retrieves a single record from the IdentityMap by the value of its
     * identity field, converting it to a $record_class object if needed.
     * 
     * @param int $identity_value The identity value of the record to be
     * retrieved.
     * 
     * @return object A record object via the record builder.
     * 
     */
    public function getRecord($identity_value)
    {
        if (! isset($this->data[$identity_value])) {
            return null;
        }
        
        if ($this->data[$identity_value] instanceof $this->record_class) {
            return $this->data[$identity_value];
        }
        
        $data = $this->data[$identity_value];
        $record = $this->record_builder->newInstance($this, $data);
        $this->data[$identity_value] = $record;
        
        return $this->data[$identity_value];
    }
    
    /**
     * 
     * Retrieves the first record from the IdentityMap that matches the value
     * of an arbitrary field; it will be converted to a record object
     * if it is not already an object of the proper class.
     * 
     * N.b.: This will not be performant for large sets where the field is not
     * an identity field and is not indexed.
     * 
     * @param string $field The field to match on.
     * 
     * @param mixed $value The value of the field to match on.
     * 
     * @return object A record object via the record builder.
     * 
     */
    public function getRecordByField($field, $value)
    {
        // pre-emptively look for an identity field
        if ($field == $this->identity_field) {
            return $this->getRecord($value);
        }
        
        // pre-emptively look for an indexed field for that value
        if (isset($this->index_fields[$field][$value])) {
            $identity_value = reset($this->index_fields[$field][$value]);
            return $this->getRecord($identity_value);
        }
        
        // long slow loop through all the records to find a match.
        foreach ($this->data as $identity_value => $record) {
            if ($record->$field == $value) {
                return $this->getRecord($identity_value);
            }
        }
        
        // no match!
        return null;
    }
    
    /**
     * 
     * Retrieves a collection of elements from the IdentityMap by the values
     * of their identity fields; each entity will be converted to a record 
     * object if it is not already an object of the proper class.
     * 
     * @param array $identity_values An array of identity values to retrieve.
     * 
     * @return object A collection object via the collection builder.
     * 
     */
    public function getCollection(array $identity_values)
    {
        $list = array();
        foreach ($identity_values as $identity_value) {
            // assigning by reference keeps the connections
            // when the entity is converted to a record
            $list[] =& $this->data[$identity_value];
        }
        return $this->collection_builder->newInstance($this, $list);
    }
    
    /**
     * 
     * Retrieves a collection of objects from the IdentityMap matching the 
     * value of an arbitrary field; these will be converted to records 
     * if they are not already objects of the proper class.
     * 
     * The value to be matched can be an array of values, so that you
     * can get many values of the field being matched.
     * 
     * If the field is indexed, the order of the returned collection
     * will match the order of the values being searched. If the field is not
     * indexed, the order of the returned collection will be the same as the 
     * IdentityMap.
     * 
     * The fastest results are from the identity field; second fastest, from
     * an indexed field; slowest are from non-indexed fields, because it has
     * to look through the entire IdentityMap to find matches.
     * 
     * @param string $field The field to match on.
     * 
     * @param mixed $values The value of the field to match on; if an array,
     * any value in the array will be counted as a match.
     * 
     * @return object A collection object via the collection builder.
     * 
     */
    public function getCollectionByField($field, $values)
    {
        $values = (array) $values;
        
        // pre-emptively look for an identity field
        if ($field == $this->identity_field) {
            return $this->getCollection($values);
        }
        
        // pre-emptively look for an indexed field
        if (isset($this->index_fields[$field])) {
            return $this->getCollectionByIndex($field, $values);
        }
        
        // long slow loop through all the records to find a match
        $list = array();
        foreach ($this->data as $identity_value => $record) {
            if (in_array($record->$field, $values)) {
                // assigning by reference keeps the connections
                // when the original is converted to a record
                $list[] =& $this->data[$identity_value];
            }
        }
        return $this->collection_builder->newInstance($this, $list);
    }
    
    /**
     * 
     * Looks through the index for a field to retrieve a collection of
     * objects from the IdentityMap; these will be converted to records 
     * if they are not already objects of the proper class.
     * 
     * N.b.: The value to be matched can be an array of values, so that you
     * can get many values of the field being matched.
     * 
     * N.b.: The order of the returned collection will match the order of the
     * values being searched, not the order of the records in the IdentityMap.
     * 
     * @param string $field The field to match on.
     * 
     * @param mixed $values The value of the field to match on; if an array,
     * any value in the array will be counted as a match.
     * 
     * @return object A collection object via the collection builder.
     * 
     */
    public function getCollectionByIndex($field, $values)
    {
        $values = (array) $values;
        $list = array();
        foreach ($values as $value) {
            // is there an index for that field value?
            if (isset($this->index_fields[$field][$value])) {
                // assigning by reference keeps the connections
                // when the original is converted to a record.
                foreach ($this->index_fields[$field][$value] as $identity_value) {
                    $list[] =& $this->data[$identity_value];
                }
            }
        }
        return $this->collection_builder->newInstance($this, $list);
    }
    
    /**
     * 
     * Sets a relationship to another type, assigning it to a field
     * name to be used in record objects.
     * 
     * @param string $name The field name to use for the related record
     * or collection.
     * 
     * @param RelationInterface $relation The relationship definition object.
     * 
     * @return void
     * 
     */
    public function setRelation($name, RelationInterface $relation)
    {
        if (isset($this->relation[$name])) {
            throw new Exception("Relation '$name' already exists.");
        }
        $this->relation[$name] = $relation;
    }
    
    /**
     * 
     * Returns a relationship definition object by name.
     * 
     * @return AbstractRelation
     * 
     */
    public function getRelation($name)
    {
        return $this->relation[$name];
    }
    
    /**
     * 
     * Returns all the names of the relationship definition objects.
     * 
     * @return array
     * 
     */
    public function getRelationNames()
    {
        return array_keys($this->relation);
    }
}