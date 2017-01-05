# Ethereal Laravel

This package extends and changes some laravel functionalities.

> Minimum required laravel version is 5.3.
 
- [Model](#model)
 
## Model

This package provides extended laravel Model, named `Ethereal`. This model works a bit different in few use cases:

- all fillable and guarded functionality is disabled;
> It's the only reasonable way to work with Eloquent and prevents the use of request()->all(). This also improves code readability where you don't have to guess what request 
data you'll receive as you'll have to specify it if you're going to fill it to the model.
- `columns` model field is provided
> This field is used to get dirty columns for model save or update, so you can have as many custom attributes in there as you want. If the column is empty (default), all 
attributes are held as possible table columns.
- model validation is available
> Methods like `valid` and `validOrFail` are provided to validate the model. `fullyValid` is present if you want to validate the model and all it's relations. This uses 
laravel's validator so after any validation action you can call `validator` to get the last used validator. Model validation rules are determined by `validationRules` method - 
sometimes rules change depending on model existence, to determine model existence you can use `$this->exists` model field.