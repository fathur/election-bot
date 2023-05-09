"""CreatePollsTable Migration."""

from masoniteorm.migrations import Migration


class CreatePollsTable(Migration):
    def up(self):
        """
        Run the migrations.
        """
        with self.schema.create("polls") as table:
            table.increments("id")
            table.integer("tweet_id")
            table.string("object_id")
            table.string("url")
            table.datetime("start_at")
            table.datetime("end_at")
            table.unsigned_integer("total_voter", nullable=True)
            table.timestamps()

            table.foreign("tweet_id").references("id").on("tweets")

    def down(self):
        """
        Revert the migrations.
        """
        self.schema.drop("polls")
