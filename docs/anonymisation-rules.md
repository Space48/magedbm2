# Defining Anonymisation Rules

As well as stripping certain tables in your projects, you might also have project-specific columns containing sensitive data that you do not want exported. To do this you need to define which formatter to apply to the column.

Depending on the type of entity that you want to anonymise you'll either need to use the `tables` or the `eav` key in the configuration.  Assuming that we've added a new table called `super_pay` with two columns that need anonymisation and we've added a new attribute to customers.

    anonymizer:
      tables:
        - name: super_pay
          columns:
            customer_card_number: Faker\Provider\Payment::creditCardNumber
            receipt_email: Faker\Provider\Internet::email
      eav:
        - entity: customer
          attributes:
            super_pay_tracking_id: Meanbee\Magedbm2\Anonymizer\Formatter\Rot13 # lol.

You have access to the [Faker](https://github.com/fzaninotto/Faker) library.

The format of the formatter string is either `${className}` if the class implements `Meanbee\Magedbm2\Anonymizer\FormatterInterface` or `${className}::${method}` if you want to call a method directly.