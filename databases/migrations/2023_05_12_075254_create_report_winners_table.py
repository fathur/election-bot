"""CreateReportWinnersTable Migration."""

from masoniteorm.migrations import Migration


class CreateReportWinnersTable(Migration):
    def up(self):
        """
        Run the migrations.
        """
        with self.schema.create("report_winners") as table:
            table.increments("id")
            table.unsigned_integer('report_id')
            table.unsigned_integer('poll_choice_id')
            table.unsigned_integer('total_voters')
            table.timestamps()

            table.foreign("report_id").references("id").on("reports")
            table.foreign("poll_choice_id").references("id").on("poll_choices")

    def down(self):
        """
        Revert the migrations.
        """
        self.schema.drop("report_winners")
