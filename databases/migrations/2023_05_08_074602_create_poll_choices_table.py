"""CreatePollChoicesTable Migration."""

from masoniteorm.migrations import Migration


class CreatePollChoicesTable(Migration):
    def up(self):
        """
        Run the migrations.
        """
        with self.schema.create("poll_choices") as table:
            table.increments("id")
            table.string("option")
            table.timestamps()

    def down(self):
        """
        Revert the migrations.
        """
        self.schema.drop("poll_choices")
