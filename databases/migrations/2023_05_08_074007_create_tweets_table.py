"""CreateTweetsTable Migration."""

from masoniteorm.migrations import Migration


class CreateTweetsTable(Migration):
    def up(self):
        """
        Run the migrations.
        """
        with self.schema.create("tweets") as table:
            table.increments("id")
            table.integer("account_id", nullable=True)
            table.string("object_id")
            table.string("url", nullable=True)
            table.text("text")
            table.timestamps()

            table.foreign("account_id").references("id").on("accounts")

    def down(self):
        """
        Revert the migrations.
        """
        self.schema.drop("tweets")
