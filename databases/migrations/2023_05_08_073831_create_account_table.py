"""CreateAccountTable Migration."""

from masoniteorm.migrations import Migration


class CreateAccountTable(Migration):
    def up(self):
        """
        Run the migrations.
        """
        with self.schema.create("accounts") as table:
            table.increments("id")
            table.string('object_id')
            table.string('username')
            table.string('name')

            table.timestamps()

    def down(self):
        """
        Revert the migrations.
        """
        self.schema.drop("accounts")
