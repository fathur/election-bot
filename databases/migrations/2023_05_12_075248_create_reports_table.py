"""CreateReportsTable Migration."""

from masoniteorm.migrations import Migration


class CreateReportsTable(Migration):
    def up(self):
        """
        Run the migrations.
        """
        with self.schema.create("reports") as table:
            table.increments("id")
            table.string('interval')
            table.datetime('start_at')
            table.datetime('end_at')
            table.unsigned_integer('total_voters', nullable=True)
            table.json('related_tweets', nullable=True)
            table.json('winner_resume', nullable=True)
            table.timestamps()

    def down(self):
        """
        Revert the migrations.
        """
        self.schema.drop("reports")
