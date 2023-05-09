"""CreatePollResultTable Migration."""

from masoniteorm.migrations import Migration


class CreatePollResultTable(Migration):
    def up(self):
        """
        Run the migrations.
        """
        with self.schema.create("poll_results") as table:
            table.increments("id")
            table.integer("poll_id")
            table.integer("poll_choice_id")
            table.unsigned_integer("total_voter", nullable=True)
            table.timestamps()

            table.foreign("poll_id").references("id").on("polls")
            table.foreign("poll_choice_id").references("id").on("poll_choices")

    def down(self):
        """
        Revert the migrations.
        """
        self.schema.drop("poll_results")
