import math
import os
import sentry_sdk

import click
import tweepy

from app.services.poll_maker import PollMaker
from app.services.reporting import Reporting


@click.group()
def run():
    pass


@run.command()
def create_poll():
    click.echo("Executing create poll")
    PollMaker.run()
    click.echo("Executed create poll")


@run.command()
@click.option(
    "-i",
    "--interval",
    required=True,
    show_default=True,
    type=click.Choice(
        ["daily", "weekly", "monthly", "quarterly", "yearly"], case_sensitive=True
    ),
)
def generate_report(interval):
    import pendulum

    click.echo(f"Executing generate report {interval}")
    Reporting.run(interval, end_at=pendulum.datetime(2023, 5, 13))
    click.echo(f"Executed generate report {interval}")
