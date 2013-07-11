// This code generated by Zoop\Shard\Dojo
define([
    'dojo/_base/declare',    
    'havok/form/ValidationTextBox',
    'shard/simple/NameValidator'
],
function(
    declare,    
    ValidationTextBox,
    NameValidator
){
    // Will return an input for the name field

    return declare(
        [            
            ValidationTextBox        
        ],
        {
            validator: new NameValidator,
            
            name: 'name',
            
            label: 'NAME',
            
            tooltip: 'The document name',
            
            description: 'This is a longer description'
        }
    );
});
