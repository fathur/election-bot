"""PollChoiceTableSeeder Seeder."""

from masoniteorm.seeds import Seeder

from app.models import PollChoice


class PollChoiceTableSeeder(Seeder):
    def run(self):
        """Run the database seeds."""
        options = ["Anies Baswedan", "Prabowo Subianto", "Ganjar Pranowo"]
        for item in options:
            PollChoice.create({
                "option": item
            })
